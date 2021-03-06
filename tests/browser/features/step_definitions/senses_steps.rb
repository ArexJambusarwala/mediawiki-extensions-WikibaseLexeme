Then(/^Senses header should be there$/) do
  expect(on(LexemePage).senses_header?).to be true
end

Then(/^Senses container should be there$/) do
  expect(on(LexemePage).senses_container?).to be true
end

Then(/^I see at least one Sense$/) do
  expect(on(LexemePage).senses.count).to be > 0
end

Given(/^for each Sense there is an anchor equal to its ID$/) do
  on(LexemePage).senses.each do |senses|
    id = senses.id_element.when_visible.text.sub('(', '').sub(')', '')
    anchor = senses.anchor

    expect(anchor).to be == id
  end
end

Then(/^for each Sense there is a gloss and an ID$/) do
  on(LexemePage).senses.each do |sense|
     expect(sense.glosses.count).to be > 0
     expect(sense.id?).to be true
  end
end

Then(/^for each Sense there is a statement list$/) do
  on(LexemePage).senses.each do |sense|
    expect(sense.statements?).to be true
  end
end

Given(/^there is a Sense to test$/) do
  # TODO: Creating the Sense should be done on the backend once it is possible
  step 'I click on the Senses list add button'
  step 'I add a Gloss for "en" language with value "some gloss"'
  step 'I save the Sense'
end

When(/^I click the first Sense's edit button$/) do
  @sense_i_am_currently_editing = on(LexemePage).senses[0]
  @sense_i_am_currently_editing.edit_element.when_visible.click
end

When(/^I change the text of the first Gloss definition$/) do
  gloss_value = generate_random_string(20)

  gloss_i_am_currently_editing = @sense_i_am_currently_editing.glosses[0]

  gloss_i_am_currently_editing.value_input_element.when_visible.clear
  gloss_i_am_currently_editing.value_input = gloss_value

  language_code = gloss_i_am_currently_editing.language_input_element.attribute('value')
  @new_gloss = { value: gloss_value, language: language_code }
end

And(/^I add a Gloss for "(.*?)" language with value "(.*?)"$/) do |language_code, gloss_value|
  @sense_i_am_currently_editing.add_gloss_element.when_present.when_visible.click
  new_gloss = @sense_i_am_currently_editing.glosses[-1]
  new_gloss.language_input = language_code
  new_gloss.value_input = gloss_value
  @new_gloss = { value: gloss_value, language: language_code }
end

And(/^I remove the first Gloss definition$/) do
  removed_gloss = @sense_i_am_currently_editing.glosses[0]

  @removed_gloss_language = removed_gloss.language_input_element.when_present.value
  @removed_gloss_value = removed_gloss.value_input_element.when_present.value

  removed_gloss.remove_element.when_visible.click
end

When(/^I save the Sense$/) do
  @sense_i_am_currently_editing.save_element.when_visible.click
end

Then(/^I should see Gloss with value "(.*?)" for "(.*?)" language$/) do |gloss_value, language_code|
  expect(@sense_i_am_currently_editing.gloss?(language_code, gloss_value)).to be true
end

Then(/^I should see the new text as the Gloss definition$/) do
  expect(@sense_i_am_currently_editing.gloss?(@new_gloss[:language], @new_gloss[:value])).to be true
end

Then(/^I don't see that Gloss definition$/) do
  expect(@sense_i_am_currently_editing.gloss?(@removed_gloss_language, @removed_gloss_value)).to be false
end

When(/^I click on the Senses list add button$/) do
  on(LexemePage).add_sense_element.when_visible.click
  @sense_i_am_currently_editing = on(LexemePage).senses[-1]
end

Then(/^I should see a new Sense with that Gloss$/) do
  last_sense_gloss = on(LexemePage).senses[-1].glosses[-1]
  expect(last_sense_gloss.language_element.when_visible.text).to eq(@new_gloss[:language])
  expect(last_sense_gloss.value_element.when_visible.text).to eq(@new_gloss[:value])
end
