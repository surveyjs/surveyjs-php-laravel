// Load JSON Schemas, Variables, and Answers
import { mountSurvey, note, onStored } from "./host.js";

// #region sjs:load-definition-variables-data.client
import { Model } from "survey-core";

async function createSurvey(recordId) {
  if (window.FORM_DEFINITION) {
    // Option A — your server rendered the definition into the page
    return new Model(window.FORM_DEFINITION);
  }
  // Option B — one request returns the definition, variables and previous answers
  const { definition, variables, data } =
    await fetch(`/api/forms/claim-request?record=${recordId}`).then(r => r.json());
  const survey = new Model(definition);
  survey.setVariables(variables);   // all at once; read as {user.name}, {plan}… and never saved with the response
  survey.data = data;               // previous answers: edit instead of starting blank
  return survey;
}
// #endregion

const recordId = new URLSearchParams(location.search).get("record") ?? "";
const survey = mountSurvey(await createSurvey(recordId));

// Saving the finished response is in save-response: post survey.data, which holds answers only
let savedResponseId = null;
survey.onComplete.add(async (sender, options) => {
  options.showSaveInProgress();
  try {
    const response = await fetch("/api/responses", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ formId: "claim", data: sender.data })
    });
    if (!response.ok) throw new Error("Save failed");
    const { id } = await response.json();
    if (!Number.isInteger(id) || id < 1) throw new Error("Missing response ID");
    savedResponseId = id;
    options.showSaveSuccess();
  } catch {
    options.showSaveError("Couldn't save the response. Please try again.");
  }
});

// Demo only: show that the stored response carries no variables
onStored((stored) => {
  const row = stored.responses?.find((response) => response.id === savedResponseId);
  if (!row) return;
  savedResponseId = null;
  const keys = Object.keys(row.data ?? {});
  const leaked = keys.filter((key) => ["user", "plan", "variables"].includes(key));
  note(leaked.length
    ? `Response #${row.id} contains ${leaked.join(", ")}: variables were saved.`
    : `Response #${row.id} saved. Only submitted answers were stored; form variables were excluded.`, leaked.length ? "error" : "ok");
});

document.getElementById("start-over")?.addEventListener("click", () => {
  savedResponseId = null;
  survey.clear(false, true);
});
