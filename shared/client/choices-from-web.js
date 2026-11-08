// Load Choices from Web
import { mountSurvey, page, show } from "./host.js";
import { Model } from "survey-core";

// Demo only: a stand-in for the access token your app already has
const token = "demo-token";

// #region sjs:choices-from-web.client
// Configure each list in the form definition
//   "choicesByUrl": { "url": "/api/offices?region={region}", "valueName": "id", "titleName": "name" }
//   "choicesByUrl": { "url": "/api/countries", "valueName": "code", "titleName": "name" }
// Replace {region} with the selected region and reload the offices when it changes

// Optional: add your auth header to every choices request
import { settings } from "survey-core";
settings.web.onBeforeRequestChoices = (_, options) => {
  options.request?.setRequestHeader("Authorization", `Bearer ${token}`);
};
// #endregion

const survey = mountSurvey(new Model(page.definition));

// Clear the previous selection immediately, before the new office list arrives
survey.onValueChanged.add((sender, { name }) => {
  if (name === "region") sender.clearValue("office");
});

// Demo only: show labels and the values an application would receive
const summary = document.getElementById("choice-summary");
survey.onComplete.add((sender) => {
  show("selected-office", sender.getQuestionByName("office").displayValue);
  show("selected-country", sender.getQuestionByName("country").displayValue);
  show("selected-values", JSON.stringify(sender.data, null, 2));
  summary.hidden = false;
});
document.getElementById("start-over")?.addEventListener("click", () => {
  summary.hidden = true;
  survey.clear(true, true);
});
