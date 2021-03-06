Feature:
  In order to have insights on the ongoing platform
  As a decider
  I need to have access to some metrics

  Scenario: A tide failed
    Given a tide is started with the UUID "4c1974ae-8bb6-4aac-a620-9e66acc91968"
    When the tide failed
    Then a "tides" event should be sent to logitio with the UUID "4c1974ae-8bb6-4aac-a620-9e66acc91968" and status code 500

  Scenario: A tide succeeded
    Given a tide is started with the UUID "4c1974ae-8bb6-4aac-a620-9e66acc91968"
    When the tide is successful
    Then a "tides" event should be sent to logitio with the UUID "4c1974ae-8bb6-4aac-a620-9e66acc91968" and status code 200

  Scenario: A build task succeed
    Given there is 1 application images in the repository
    And a tide is started with a build task
    When the build succeed
    Then a "tides_tasks" event should be sent to logitio with the status code 200

  Scenario: A build task failed
    Given there is 1 application images in the repository
    And a tide is started with a build task
    And the build is failing
    Then a "tides_tasks" event should be sent to logitio with the status code 500

  Scenario: A deploy task succeed
    Given a tide is started with a deploy task
    When the deployment succeed
    Then a "tides_tasks" event should be sent to logitio with the status code 200
