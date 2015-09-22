Feature:
  In order to display tide information
  As a developer
  I need to be able to have a view representation of the tide

  Background:
    Given there is 1 application images in the repository

  Scenario:
    When a tide is created
    Then a tide view representation should have be created
    And the tide is represented as pending

  Scenario:
    When a tide is started with a build task
    Then the tide is represented as running

  Scenario:
    When a tide is started
    And the tide failed
    Then the tide is represented as failed
