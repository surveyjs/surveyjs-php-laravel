// Lint JSON Schemas
import { note, page, show } from "./host.js";
import { SurveyCreator, renderSurveyCreator } from "survey-creator-js";
import { responseError } from "./schema-saver.js";

const formId = "support-lint";
const creator = new SurveyCreator({ showTranslationTab: true });
const editor = document.getElementById("creator");
const retry = document.getElementById("retry-load");
const buttons = [...document.querySelectorAll("[data-lint]")];
let saving = false;

function reportFindings(report) {
  const findings = [
    ...(report.errors ?? []).map(finding => ({ ...finding, severity: "Error" })),
    ...(report.warnings ?? []).map(finding => ({ ...finding, severity: "Warning" }))
  ];
  document.getElementById("lint-findings").replaceChildren(...findings.map(finding => {
    const row = document.createElement("tr");
    for (const value of [finding.severity, finding.ruleId ?? finding.type ?? "—", finding.path ?? finding.elementName ?? "—", finding.message ?? finding.text ?? "Check the schema"]) {
      const cell = document.createElement("td");
      cell.className = "border-t border-gray-200 px-3 py-2 align-top";
      cell.textContent = typeof value === "string" ? value : JSON.stringify(value);
      row.append(cell);
    }
    return row;
  }));
  document.getElementById("lint-report").hidden = !findings.length;
}

// #region sjs:lint-definition.client
// Display the server's errors and warnings and return the actual save result to Creator
async function saveDefinition(definition) {
  show("lint-payload", JSON.stringify(definition, null, 2));
  const response = await fetch(`/api/forms/${formId}`, {
    method: "PUT", headers: { "Content-Type": "application/json" }, body: JSON.stringify(definition)
  });
  const report = response.status === 204 ? {} : await response.json();
  reportFindings(report);
  if (response.status === 422 && Array.isArray(report.errors) && report.errors.length) {
    show("lint-status", `Schema not saved. Fix ${report.errors.length} reported error${report.errors.length === 1 ? "" : "s"} and try again.`);
    return false;
  }
  if (!response.ok) throw new Error(report.error || "Couldn't save the form schema. Please try again.");
  const count = report.warnings?.length ?? 0;
  show("lint-status", count ? `Schema saved with ${count} warning${count === 1 ? "" : "s"}. Review the findings below.`
    : page.switches.lintDefinitions ? "Schema saved. No findings." : "Schema saved. Server schema checking is disabled.");
  return true;
}
creator.saveSurveyFunc = async (saveNo, callback) => callback(saveNo, await checkAndSave(creator.JSON));
// #endregion

async function checkAndSave(definition) {
  if (saving) return false;
  saving = true;
  editor.inert = true;
  for (const button of buttons) button.disabled = true;
  reportFindings({});
  show("lint-status", "Checking and saving the schema...");
  try { return await saveDefinition(definition); }
  catch (error) {
    show("lint-status", error instanceof TypeError || error instanceof SyntaxError ? "Couldn't save the form schema. Please try again." : error.message);
    return false;
  } finally {
    saving = false;
    editor.inert = false;
    for (const button of buttons) button.disabled = false;
  }
}

// Build deliberately invalid samples from a fixed definition so each check is repeatable
function sample(change) {
  const definition = structuredClone(page.definition);
  change(definition.elements);
  return definition;
}
const cases = {
  "unknown-variable": () => sample(elements => { elements[0].visibleIf = "{nosuch} = 1"; }),
  "no-name": () => sample(elements => elements.push({ type: "text", title: "A question without a name" })),
  "unknown-property": () => sample(elements => { elements[0].someproperty = 1; }),
  "clean": () => creator.JSON,
};
for (const button of buttons) button.addEventListener("click", async () => {
  const saved = await checkAndSave(cases[button.dataset.lint]());
  note(saved ? "Form schema saved successfully." : "Form schema not saved. Fix the reported errors and retry.", saved ? "ok" : "error");
});

async function load() {
  retry.hidden = true;
  for (const button of buttons) button.disabled = true;
  show("lint-status", "Loading the saved schema...");
  try {
    const response = await fetch(`/api/forms/${formId}`);
    if (!response.ok) throw await responseError(response, "Couldn't load the form schema. Please try again.");
    const result = await response.json();
    if (!result.definition || typeof result.definition !== "object" || Array.isArray(result.definition)) throw new Error("The saved schema couldn't be loaded. Please try again.");
    creator.JSON = result.definition;
    renderSurveyCreator(creator, editor);
    editor.hidden = false;
    editor.inert = false;
    for (const button of buttons) button.disabled = false;
    show("lint-status", "No schema checked");
  } catch (error) {
    show("lint-status", error instanceof TypeError ? "Couldn't load the form schema. Please try again." : error.message);
    retry.hidden = false;
  }
}
retry.addEventListener("click", load);
load();
