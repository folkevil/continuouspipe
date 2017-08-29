Feature:
  In order to use ContinuousPipe and know what I am paying for
  As a user
  I want to be able to know what are the available plans and to chose one.

  Background:
    Given I am authenticated

  Scenario: List the plans
    When I request the list of available plans
    Then I should see the following plans:
     | identifier | name    | metrics.tides | metrics.memory | price | metrics.docker_image | metrics.storage |
     | starter    | Starter | 100            | 4.5             | 150   | 1                    | 5                |
     | lean       | Lean    | 250            | 9               | 320   | 1                    | 10               |
     | medium     | Medium  | 500            | 15              | 575   | 1                    | 20               |
     | large      | Large   | 1500           | 30              | 1475  | 5                    | 100              |

  Scenario: List the add-ons
    When I request the list of available add-ons
    Then I should see the following add-ons:
     | identifier  | name                       | price | metrics.tides | metrics.memory | metrics.docker_image | metrics.storage |
     | 50tides     | 50 extra deployments       | 25    | 50             | 0               | 0                    | 0                |
     | memory      | Extra GB of memory         | 20    | 0              | 1               | 0                    | 0                |
     | dockerimage | Private Docker image       | 5     | 0              | 0               | 1                    | 0                |
     | 5gbstorage  | 5 GB of persistent storage | 5     | 0              | 0               | 0                    | 5                |
