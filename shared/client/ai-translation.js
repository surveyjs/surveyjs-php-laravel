// Translate with AI
import { note, show } from "./host.js";
import { SurveyCreator, renderSurveyCreator } from "survey-creator-js";
import { createSchemaSaver, responseError } from "./schema-saver.js";
import "survey-core/i18n/german";
import "survey-core/i18n/french";
import "survey-core/i18n/spanish";

const formId = "support-translations";
const creator = new SurveyCreator({ showTranslationTab: true });
const element = document.getElementById("creator");
const language = document.getElementById("translation-language");
const translate = document.getElementById("translate-language");
const preview = document.getElementById("preview-language");
const save = document.getElementById("save-translations");
const retryLoad = document.getElementById("retry-load");
let loaded = false, translating = 0, saving = false, translationRuns = 0, previewSurvey;

const saver = createSchemaSaver(() => creator.JSON, async json => {
  const response = await fetch(`/api/forms/${formId}`, { method: "PUT", headers: { "Content-Type": "application/json" }, body: json });
  if (!response.ok) throw await responseError(response, "Couldn't save the translations. Please try again.");
}, ({ state, error }) => {
  if (!loaded) return;
  saving = state === "saving";
  show("schema-status", saving ? "Saving translations..." : state === "saved" ? "All changes saved."
    : state === "error" ? `${error} Your translations remain in the editor.` : "Unsaved changes. Review the translations, then save.");
  updateControls();
});
creator.saveSurveyFunc = (saveNo, callback) => saver.save().then(saved => callback(saveNo, saved));
creator.onModified.add(() => { if (loaded) saver.changed(); });
creator.onSurveyInstanceCreated.add((_, options) => {
  if (options.area === "preview-tab") { previewSurvey = options.survey; previewSurvey.locale = language.value; }
});

// #region sjs:ai-translation.client
// Return translated strings in the original order, and always finish the callback
creator.onMachineTranslate.add(async (_, options) => {
  translating++;
  translationRuns++;
  updateControls();
  show("translation-status", `Translating into ${options.toLocale}...`);
  try {
    const response = await fetch("/api/translate", {
      method: "POST", headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ strings: options.strings, from: options.fromLocale, to: options.toLocale })
    });
    if (!response.ok) throw await responseError(response, "AI translation failed. Please try again.");
    const strings = await response.json();
    if (!Array.isArray(strings) || strings.length !== options.strings.length || strings.some(text => typeof text !== "string")) throw new Error("The translation provider returned an invalid result. Please try again.");
    options.callback(strings);
    show("translation-status", "Translation ready. Review the text in Translations before saving.");
    saver.changed();
  } catch (error) {
    options.callback([]);
    show("translation-status", `${translationError(error)} Existing translations are kept.`);
    note(error.message, "error");
  } finally { translating--; updateControls(); }
});
// #endregion

function translationError(error) {
  return error instanceof TypeError ? "Couldn't reach the translation service. Please try again." : error.message;
}

function updateControls() {
  translate.disabled = !loaded || translating > 0 || saving || language.value === "en";
  preview.disabled = !loaded || translating > 0 || saving;
  language.disabled = !loaded || translating > 0 || saving;
  save.disabled = !loaded || translating > 0 || saving || !saver.dirty;
  element.inert = !loaded || translating > 0 || saving;
}
function selectLanguage() {
  const translation = creator.getPlugin("translation").model;
  translation?.setSelectedLocales([language.value === "en" ? "default" : language.value]);
  if (previewSurvey) previewSurvey.locale = language.value;
  updateControls();
}
translate.addEventListener("click", () => {
  creator.switchTab("translation");
  selectLanguage();
  const before = translationRuns;
  creator.startMachineTranslationTo([language.value]);
  if (before === translationRuns) show("translation-status", "This language has no untranslated text. Review or edit its translations below.");
});
preview.addEventListener("click", () => {
  creator.switchTab("preview");
  if (previewSurvey) previewSurvey.locale = language.value;
});
language.addEventListener("change", selectLanguage);
save.addEventListener("click", () => creator.saveSurvey());
async function loadDefinition() {
  retryLoad.hidden = true;
  show("schema-status", "Loading the saved form...");
  try {
    const response = await fetch(`/api/forms/${formId}`);
    if (!response.ok) throw await responseError(response, "Couldn't load the form schema. Please try again.");
    const { definition } = await response.json();
    if (!definition || typeof definition !== "object" || Array.isArray(definition)) throw new Error("The server returned an invalid form schema.");
    creator.JSON = definition;
    renderSurveyCreator(creator, element);
    creator.switchTab("translation");
    const translation = creator.getPlugin("translation").model;
    ["de", "fr", "es"].forEach(locale => translation.addLocale(locale));
    loaded = true;
    saver.markSaved();
    element.hidden = false;
    selectLanguage();
  } catch (error) { show("schema-status", error.message); retryLoad.hidden = false; }
  updateControls();
}
retryLoad.addEventListener("click", loadDefinition);
updateControls();
await loadDefinition();
