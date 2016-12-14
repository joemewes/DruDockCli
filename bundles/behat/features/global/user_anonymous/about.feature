@anon @javascript @screenshot @about
Feature: About us section
  In order to fine out about DataPoints
  As any user
  I want to find information About us

  @parallel-scenario
  Scenario: Browse to Home page
    Given I am on the homepage
    Then I should see "Welcome to DockerDrupal"
