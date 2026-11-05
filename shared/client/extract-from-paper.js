// Extract Responses from Documents
import { mountSurvey, note, page, show } from "./host.js";
import { Model } from "survey-core";

const survey = mountSurvey(new Model(page.definition));   // the work order form

// #region sjs:extract-from-paper.client
// Upload the scan; open the draft in the form for review
async function extract(file) {
  const body = new FormData();
  body.append("scan", file);
  const res = await fetch("/api/work-orders/extract", { method: "POST", body });
  const result = await res.json();
  if (!res.ok) throw new Error(result.error);
  survey.data = result.answers;   // a person checks and corrects, then saves as usual
  return result;                  // { answers, confidence, uniqueId }
}
// #endregion

// Demo only: highlight low-confidence answers and show the unique id
async function run(file) {
  try {
    document.getElementById("extract-details").hidden = true;
    show("extract-status", `Extracting answers from ${file.name}…`);
    const { confidence, uniqueId } = await extract(file);
    for (const question of survey.getAllQuestions()) question.description = "";
    const flagged = confidence.filter((c) => c.flagged);
    for (const c of flagged) {
      const question = survey.getQuestionByName(c.fieldName);
      if (question) question.description = `Review this answer (${Math.round(c.confidence * 100)}% confidence)`;
    }
    show("extract-status", "Draft ready for review");
    show("extract-source", file.name);
    show("extract-id", uniqueId ?? "Not detected");
    show("extract-review-count", String(flagged.length));
    document.getElementById("extract-details").hidden = false;
    note("Draft ready for review", "ok");
  } catch (e) {
    show("extract-status", e.message);
    note(e.message, "error");
  }
}
document.getElementById("sample-scan")?.addEventListener("click", async () => {
  const blob = await fetch("/shared/samples/work-order-scan.png").then((r) => r.blob());
  run(new File([blob], "work-order-scan.png", { type: "image/png" }));
});
document.getElementById("own-scan")?.addEventListener("change", (e) => e.target.files[0] && run(e.target.files[0]));
document.getElementById("start-over")?.addEventListener("click", () => {
  survey.clear(true, true);
  for (const question of survey.getAllQuestions()) question.description = "";
  document.getElementById("own-scan").value = "";   // so picking the same file again extracts it again
  show("extract-status", "No scan");
  document.getElementById("extract-details").hidden = true;
  for (const field of ["extract-source", "extract-id", "extract-review-count"]) show(field, "");
});
