// Variable Presets
import { page, show } from "./host.js";
import { SurveyCreator, renderSurveyCreator } from "survey-creator-js";
import { createSchemaSaver, responseError } from "./schema-saver.js";

const formId = "support";
const element = document.getElementById("creator");
const retryLoad = document.getElementById("retry-load");
const retrySave = document.getElementById("retry-presets");
let loaded = false;

// #region sjs:variable-presets.client
const creator = new SurveyCreator({
  variablePresets: {
    definition: {
      elements: [{
        type: "dropdown", name: "customerTier", title: "Customer plan",
        choices: [{ value: "basic", text: "Basic" }, { value: "premium", text: "Premium" }],
        isRequired: true
      }]
    },
    presets: []
  }
});
creator.onVariablePresetsChanged.add((_, options) => {
  showActivePreset(options.activePresetName);
  // Switching scenarios changes only Preview; editing persists the preset list
  if (loaded && options.reason === "edit") { saver.changed(); saver.save(); }
});
// #endregion

// #region sjs:variable-presets.save
// Save the list, rather than wrapping it in another variablePresets configuration
const saver = createSchemaSaver(() => creator.variablePresets.presets ?? [], async json => {
  const response = await fetch(`/api/variable-presets/${formId}`, {
    method: "PUT", headers: { "Content-Type": "application/json" }, body: json
  });
  if (!response.ok) throw await responseError(response, "Couldn't save the presets. Please try again.");
}, ({ state, error }) => {
  if (!loaded) return;
  show("preset-status", state === "saving" ? "Saving presets..." : state === "saved" ? "Presets saved."
    : state === "error" ? `${error} Your preset edits remain in this editor.` : "Unsaved preset changes.");
  retrySave.hidden = state !== "error";
  retrySave.disabled = state === "saving";
  document.getElementById("demo-user").disabled = state === "saving";
  if (state === "error") creator.notify(error, "error");
});
// #endregion

function showActivePreset(name) {
  const preset = creator.variablePresetsModel.getPreset(name);
  show("active-preset", preset?.name ?? "None");
  show("preset-variables", JSON.stringify(preset?.variables ?? {}, null, 2));
}
async function loadPresets() {
  retryLoad.hidden = true;
  show("preset-status", "Loading presets...");
  try {
    const response = await fetch(`/api/variable-presets/${formId}`);
    if (!response.ok) throw await responseError(response, "Couldn't load the presets. Please try again.");
    const stored = await response.json();
    const presets = Array.isArray(stored) ? stored : stored?.presets;
    if (!Array.isArray(presets) || presets.some(preset => !preset || typeof preset.name !== "string" || !preset.variables || typeof preset.variables !== "object" || Array.isArray(preset.variables))) throw new Error("The server returned invalid presets.");
    creator.variablePresets = { ...creator.variablePresets, presets };
    creator.JSON = page.definition;
    renderSurveyCreator(creator, element);
    const manager = creator.getPlugin("preview").variablePresets;
    manager.active = presets.some(preset => preset.name === "basic") ? "basic" : presets[0]?.name ?? "";
    creator.switchTab("preview");
    loaded = true;
    saver.markSaved();
    showActivePreset(manager.active);
    if (!presets.length) show("preset-status", "No saved presets. Add one with Manage presets.");
    element.hidden = false;
    element.inert = false;
    document.getElementById("demo-user").disabled = false;
  } catch (error) { show("preset-status", error.message); retryLoad.hidden = false; }
}
retryLoad.addEventListener("click", loadPresets);
retrySave.addEventListener("click", () => saver.save());
await loadPresets();
