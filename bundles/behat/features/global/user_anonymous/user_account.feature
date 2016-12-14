@api @anon @javascript @useraccount
Feature: Register an account on timeshighereducation.com with valid username and email
  In order to start using additional features of the site
  As any user
  I should be able to register on the site

 @parallel-scenario
 Scenario: Login form
   Given I am on the homepage
    And I am not logged in
    And I should see "User login"
   When I fill in "Username" with "testuser"
    And I fill in "Password" with "Password1"
   When I press "Log in"
    And I wait until the page loads
   Then I should see "Sorry, unrecognized username or password"

  # @api @database @parallel-scenario @login
  # Scenario: Create and log in as a user
  #   Given I am logged in as a user with the "authenticated user" role
  #   When I click "My account"
  #   Then I should see "Current password"

  # @api @email @database @parallel-scenario
  # Scenario: Create an account
  #   Given I am on "user/register"
  #     And I fill in "E-mail" with "test_user" address
  #     And I fill in "Password" with "Password1"
  #     And I fill in "Confirm password" with "Password1"
  #     And I fill in "First Name" with "Firstname"
  #     And I fill in "Last Name" with "Lastname"
  #     And I select "Academic" from "Choose your profile"
  #     And I check the box "I agree to the cookie policy"
  #     And I check the box "I agree to the terms and conditions"
  #     And I press "Join us"
  #     And I wait until the page loads
  #   Then I should see the success message "Registration successful. You are now logged in."
  #     And I should be logged in

 @blockuser @parallel-scenario @login
 Scenario: Block an account after 5 failed login attempts
   Given users:
     | name     | mail            | status |
     | Joe User | joe@example.com | 1      |
     And I am not logged in
     And I go to "user/login"
     And I fill in "Username" with "dev"
     And I fill in "Password" with "invalid"
     And I press "Log in"
   Then I should see "Sorry, unrecognized username or password."
     And I should see the link "Have you forgotten your password?"
     And I fill in "Password" with "invalid"
     And I press "Log in"
     And I wait until the page loads
     And I fill in "Password" with "invalid"
     And I press "Log in"
     And I wait until the page loads
     And I fill in "Password" with "invalid"
     And I press "Log in"
     And I wait until the page loads
     And I fill in "Password" with "invalid"
     And I press "Log in"
     And I wait until the page loads
     And I fill in "Password" with "invalid"
     And I press "Log in"
     And I wait until the page loads
   Then I should see "Sorry, there have been more than 5 failed login attempts for this account. It is temporarily blocked. Try again later or"
    And I should see the link "request a new password"

 @authuser @parallel-scenario
 Scenario: Authenticated user profile
   Given users:
   | name      | mail             | roles              |
   | Test User | joe@example.com  | authenticated user |
   When I am logged in as "Test User"
   When I click "My account"
    And I wait until the page loads
   Then the "E-mail address" field should contain "joe@example.com"

