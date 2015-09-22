Feature:
  In order to have a granular configuration
  As a developer
  In want to be able to store my configuration under the same way both in a file in the repository and on CP side

  Scenario: The configuration is loaded from the file stored in the repository
    Given there is 1 application images in the repository
    And I have a "continuous-pipe.yml" file in my repository that contains:
    """
    tasks:
        - build: ~
    """
    When a tide is started
    Then the build task should be running

  # Note: the `deploy` task need to know where
  Scenario: The configuration file should be validated
    Given I have a "continuous-pipe.yml" file in my repository that contains:
    """
    tasks:
        - deploy: ~
    """
    When a tide is started
    Then the tide should be failed
