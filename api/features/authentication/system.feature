Feature:
  In order to have access to users credentials
  As an internal component
  I need to be able to be connected as system and access data of all users

  Scenario: If the API key do not exists, the access is refused
    Given there is a user "samuel"
    When I request the details of user "samuel" with the api key "1234"
    Then I should be told that I am not identified

  Scenario: I can authenticate with an API key as system and access to any user
    Given the is the api key "1234"
    And there is a user "samuel"
    When I request the details of user "samuel" with the api key "1234"
    Then I should receive the details
