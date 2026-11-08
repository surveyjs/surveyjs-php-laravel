// Edit Forms Together
import { inviteLink, page, participantName, relayUrl, setStoredQuery, show } from "./host.js";
import { SurveyCreator, renderSurveyCreator } from "survey-creator-js";
import { CollaborationPlugin } from "survey-creator-core/collaboration";
import { createSchemaSaver, responseError } from "./schema-saver.js";

const formId = "support-team";
const userName = participantName();
const editor = document.getElementById("creator");
const reconnect = document.getElementById("reconnect");
const retrySave = document.getElementById("retry-save");
const download = document.getElementById("download-local");
let ws, creator, collab, saver, localCopy;
let ready = false;
setStoredQuery({ room: formId });

function connected() { return ready && ws.readyState === WebSocket.OPEN; }
function send(message) { if (connected()) ws.send(JSON.stringify(message)); }

// #region sjs:edit-together.save
// Save the synchronized schema separately from the room's temporary edit log
function configureSaving() {
  saver = createSchemaSaver(() => creator.JSON, async json => {
    if (!connected()) throw new Error("Reconnect before saving the shared form.");
    const response = await fetch(`/api/forms/${formId}`, {
      method: "PUT", headers: { "Content-Type": "application/json" }, body: json
    });
    if (!response.ok) throw await responseError(response, "Couldn't save the form schema. Please try again.");
  }, ({ state, error }) => {
    show("schema-status", { saving: "Saving the form schema...", saved: "Form schema saved.", unsaved: "Shared edits haven't been saved to the database.", error }[state]);
    retrySave.hidden = state !== "error";
  });
  creator.saveSurveyFunc = async (saveNo, callback) => callback(saveNo, await saver.save());
  creator.onModified.add(() => { if (ready && !collab.isApplying) saver.changed(); });
}
// #endregion

// #region sjs:edit-together.client
// Rebuild the shared form from its saved schema and ordered edit log
function initialize(message) {
  creator?.dispose();
  creator = new SurveyCreator({ showTranslationTab: true });
  creator.JSON = message.seed;
  collab = new CollaborationPlugin(creator, { roomId: formId, getInviteLink: inviteLink });
  creator.addPlugin("collaboration", collab);
  collab.setClientId(message.clientId);
  configureSaving();
  saver.markSaved();
  if (message.log.length) collab.apply(message.log);
  const history = new Map();
  function addHistory(records) {
    for (const record of records) history.set(`${record.seq}:${record.op}:${record.payload?.target ?? ""}`, record);
    collab.setHistory([...history.values()]);
  }
  addHistory(message.log);
  const append = (_, { record }) => { send({ type: "append", payload: record }); addHistory([record]); };
  collab.onRecordAdded.add(append);
  collab.onRecordChanged.add(append);
  collab.onStateChanged.add(() => send({ type: "presence", state: collab.getState() }));
  return addHistory;
}
// #endregion

function connect() {
  if (ws && [WebSocket.CONNECTING, WebSocket.OPEN].includes(ws.readyState)) return;
  ready = false;
  editor.inert = true;
  reconnect.hidden = true;
  retrySave.disabled = true;
  show("connection-status", "Connecting to the shared form...");
  collab?.setStatus("connecting");
  ws = new WebSocket(relayUrl("edit", `/ws/forms/${formId}?name=${encodeURIComponent(userName)}`));
  let addHistory;
  ws.addEventListener("message", event => {
    const message = JSON.parse(event.data);
    if (message.type === "init") {
      addHistory = initialize(message);
      ready = true;
      saver.changed();
      if (saver.dirty) creator.setModified({ type: "COLLABORATION" });
      renderSurveyCreator(creator, editor);
      editor.hidden = false;
      editor.inert = false;
      retrySave.disabled = false;
      collab.setStatus("connected");
      send({ type: "presence", state: collab.getState() });
      show("connection-status", `Connected as ${userName}. Edits are shared live; press Save to store the schema.`);
    } else if (ready) {
      if (message.type === "record") { collab.apply(message.payload); addHistory([message.payload]); saver.changed(); creator.setModified({ type: "COLLABORATION" }); }
      if (message.type === "presence-sync") collab.setPeers(message.peers);
      if (message.type === "presence") collab.upsertPeer(message.peer);
      if (message.type === "presence-leave") collab.removePeer(message.clientId);
    }
  });
  ws.addEventListener("close", () => {
    ready = false;
    editor.inert = true;
    retrySave.disabled = true;
    if (creator) { localCopy = structuredClone(creator.JSON); download.hidden = false; }
    collab?.clearPeers();
    collab?.setStatus("closed");
    reconnect.hidden = !page.user?.isEditor;
    show("connection-status", page.user?.isEditor
      ? "Disconnected. Reconnect to load the current shared form. Download a local copy to keep this window's edits."
      : "Editor access is required to join this session. Switch Demo user to Alice to continue.");
  });
}
retrySave.addEventListener("click", () => saver.save());
reconnect.addEventListener("click", connect);
download.addEventListener("click", () => {
  const url = URL.createObjectURL(new Blob([JSON.stringify(localCopy, null, 2)], { type: "application/json" }));
  const link = Object.assign(document.createElement("a"), { href: url, download: "shared-form-local-copy.json" });
  link.click();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
});
window.addEventListener("pagehide", () => ws?.close());
connect();
