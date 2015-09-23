Feature:
  In order to build applications with private dependencies
  As a developer
  I want to be able to run build with environment variables that won't be in the final image

  Background:
    Given I am authenticated

  @integration
  Scenario:
    Given I have docker registry credentials
    When I send a build request for the fixture repository "private-dependencies" with the following environment:
    | name               | value |
    | MY_PRIVATE_ENVIRON | foo   |
    Then the build should be successful
    And the image "my/image:master" should be built
    And the command "MY_PRIVATE_ENVIRON=foo sh -c './private-check.sh'" should be ran on image "my/image:master"
    And a container should be committed with the image name "my/image:master"
    And the image "my/image:master" should be pushed
