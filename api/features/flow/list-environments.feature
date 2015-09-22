Feature:
  In order to have a feedback about the flows' deployments
  As a developer
  I want to be able to see the list of deployed environments of a flow

  Background:
    Given I am authenticated

  Scenario:
    Given I have a flow with UUID "00000000-0000-0000-0000-000000000000"
    And a tide is created with a deploy task
    And I have the a deployed environment named "00000000-0000-0000-0000-000000000000-master"
    And I have the a deployed environment named "11111111-1111-1111-1111-111111111111-bar"
    When I request the list of deployed environments
    Then I should see the environment "00000000-0000-0000-0000-000000000000-master"
    Then I should not see the environment "11111111-1111-1111-1111-111111111111-bar"

  Scenario: I receive an empty list when there's no deploy task on the flow
    Given I have a flow with UUID "00000000-0000-0000-0000-000000000000"
    And a tide is created with just a build task
    When I request the list of deployed environments
    Then I should receive an empty list of environments
