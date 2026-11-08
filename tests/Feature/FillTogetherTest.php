<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Relay\FillRelay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use stdClass;
use Tests\TestCase;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

class FillTogetherTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private array $frames = [];

    private function join(FillRelay $relay, int $id, string $room = 'test-room'): TcpConnection
    {
        $connection = $this->getMockBuilder(TcpConnection::class)->disableOriginalConstructor()->onlyMethods(['send'])->getMock();
        $connection->id = $id;
        $connection->context = new stdClass;
        $connection->method('send')->willReturnCallback(function ($data) use ($id) {
            $this->frames[$id][] = json_decode($data, true);

            return true;
        });
        $relay->onConnect($connection, new Request("GET /ws/rooms/{$room}?name=Participant{$id} HTTP/1.1\r\nHost: localhost\r\nCookie: demo_user=alice\r\n\r\n"));

        return $connection;
    }

    public function test_simultaneous_completions_save_once_and_every_participant_gets_the_same_record(): void
    {
        $relay = new FillRelay;
        $first = $this->join($relay, 1);
        $second = $this->join($relay, 2);
        $before = DB::table('responses')->where('form_id', 'job-sheet')->count();
        $relay->onMessage($first, '{"type":"complete","data":{"customer":"Acme","hours":1.0,"metadata":{}}}');
        $relay->onMessage($second, '{"type":"complete","data":{"customer":"A duplicate attempt"}}');

        $this->assertSame($before + 1, DB::table('responses')->where('form_id', 'job-sheet')->count());
        $id = $this->frames[1][1]['id'];
        $this->assertSame($id, $this->frames[2][1]['id']);
        $this->assertSame($id, $this->frames[1][2]['id']);
        $this->assertSame('{"customer":"Acme","hours":1.0,"metadata":{}}', DB::table('responses')->where('id', $id)->value('data'));
        $relay->onMessage($second, '{"type":"value","key":"customer","value":"Too late"}');
        $this->join($relay, 3);
        $this->assertSame($id, $this->frames[3][0]['responseId']);
        $this->assertSame('Acme', $this->frames[3][0]['values']['customer']);
        $this->assertSame($this->frames[1][0]['sessionId'], $this->frames[3][0]['sessionId']);
    }

    public function test_a_failed_save_can_be_retried_without_marking_the_room_as_completed(): void
    {
        $relay = new FillRelay;
        $client = $this->join($relay, 1);
        $before = DB::table('responses')->count();
        DB::statement("CREATE TRIGGER reject_job_sheet BEFORE INSERT ON responses BEGIN SELECT RAISE(FAIL, 'Simulated save failure'); END");
        $message = '{"type":"complete","data":{"customer":"Acme"}}';
        $relay->onMessage($client, $message);
        $this->assertSame('save-error', $this->frames[1][1]['type']);
        $this->assertNull($relay->roomOf($client)->responseId);
        $this->assertSame($before, DB::table('responses')->count());
        DB::statement('DROP TRIGGER reject_job_sheet');
        $relay->onMessage($client, $message);
        $this->assertSame('completed', $this->frames[1][2]['type']);
        $this->assertSame($before + 1, DB::table('responses')->count());
    }

    public function test_completion_of_one_room_does_not_complete_another(): void
    {
        $relay = new FillRelay;
        $first = $this->join($relay, 1, 'first-room');
        $second = $this->join($relay, 2, 'second-room');
        $relay->onMessage($first, '{"type":"complete","data":{"customer":"Acme"}}');
        $this->assertCount(1, $this->frames[2]);
        $this->assertNull($relay->roomOf($second)->responseId);
        $this->assertNotSame($relay->roomOf($first)->sessionId, $relay->roomOf($second)->sessionId);
    }
}
