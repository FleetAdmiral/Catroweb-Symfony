@homepage
Feature:
  In order to speed up the creation of a pocketcode program
  As UX/Design team
  We want to offer the user a library of assets to work with


  Background:
    Given there are mediapackages:
      | id | name    | name_url |
      | 1  | Looks   | looks    |
      | 2  | Sounds  | sounds   |
    And there are mediapackage categories:
      | id | name    | package | extension | priority | title_or_image |
      | 1  | Animals | Looks   | jpeg      | 0        | 0              |
      | 2  | Fantasy | Sounds  | jpeg      | 0        | 0              |
      | 3  | Bla     | Looks   | jpeg      | 0        | 1              |
      | 4  | FunFun  | Looks   | jpeg      | 0        | 2              |

    And there are mediapackage files:
      | id | name       | category | extension | active | file   | flavor                   |
      | 1  | Dog        | Animals  | png       | 1      | 1.png  | pocketcode               |
      | 2  | Bubble     | Fantasy  | mpga      | 1      | 2.mpga | pocketcode               |
      | 3  | SexyGrexy  | Bla      | png       | 0      | 3.png  |                          |
      | 4  | SexyFlavor | Animals  | png       | 1      | 4.png  | pocketflavor             |
      | 5  | SexyNULL   | Animals  | png       | 1      | 5.png  |                          |
      | 6  | SexyWolfi  | Animals  | png       | 1      | 6.png  | pocketflavor, pocketcode |

    Scenario: Viewing defined categories in a specific package
      Given I am on "/pocketcode/media-library/looks"
      Then I should see "Animals"

    Scenario: Download a media file
      When I download "/pocketcode/download-media/1"
      Then I should receive a "png" file
      And the response code should be "200"

    Scenario: The app needs the filename, so the media file link must provide the media file's name
      When I am on "/pocketcode/media-library/looks"
      Then the media file "1" must have the download url "/pocketcode/download-media/1?fname=Dog"

    Scenario: Viewing only media files for the pocketcode flavor
      Given I am on "/pocketcode/media-library/looks"
      Then I should see media file with id "1"
      And I should see media file with id "5"
      And I should see media file with id "6"
      But I should not see media file with id "4"

    Scenario: Some categories have a title, some just an image, some both
      Given I am on "/pocketcode/media-library/looks"
      Then I should see "Animals"
      And I should see "FunFun"
      But I should not see "Bla"