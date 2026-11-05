// Validate Responses
import { mountSurvey, note, page } from "./host.js";
import { Model } from "survey-core";

const survey = new Model(page.definition);   // the stored "claim" definition the server validates against

// The service reports two kinds of error: data errors { type, path } and the form's own errors { text }
const describe = (e) => e.text || e.locTextValue?.values?.default || `${e.type} at ${e.path}`;

// #region sjs:validate-response.client
// Show the server's verdict in the completion message
survey.onComplete.add(async (sender, options) => {
  options.showSaveInProgress();
  try {
    const res = await fetch("/api/responses", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ formId: "claim", data: sender.data })
    });
    if (res.ok) return options.showSaveSuccess();
    const { errors, error } = await res.json();
    options.showSaveError(errors ? errors.map(describe).join("; ") : error);
  } catch {
    options.showSaveError("Couldn't validate the response. Please try again.");
  }
});
// #endregion

mountSurvey(survey);

// Demo only: the start-over button
document.getElementById("start-over")?.addEventListener("click", () => survey.clear(true, true));

// Demo only: bypass the client and post tampered answers straight to the endpoint
const valid = { customer_email: "ann@example.com", amount: 120, incident: "theft", details: "A valid claim" };
const tampered = {
  "unknown-choice": { ...valid, incident: "meteor" },
  "wrong-type": { ...valid, amount: { much: true } },
  "missing-required": { amount: 120, incident: "theft" },
};
for (const button of document.querySelectorAll("[data-tamper]")) {
  button.addEventListener("click", async () => {
    try {
      const res = await fetch("/api/responses", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ formId: "claim", data: tampered[button.dataset.tamper] })
      });
      const body = await res.json();
      note(res.status === 400
        ? "Invalid response rejected as expected. No data was saved."
        : res.ok ? `Invalid response was accepted and saved as #${body.id}.`
          : `Couldn't validate the response. ${body.error ?? "Please try again."}`, res.status === 400 ? "ok" : "error");
    } catch {
      note("Couldn't validate the response. Please try again.", "error");
    }
  });
}
