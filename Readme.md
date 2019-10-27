# TYPO3 Extension Disable BeUser Task

[![Latest Stable Version](https://img.shields.io/packagist/v/svenjuergens/disable_beuser.svg)](https://packagist.org/packages/svenjuergens/belogin_images)

This extension integrate a scheduler task to disable backend user after a configurable amount of time.
## Installation

Simply install the extension with Extension Manager or composer
`composer require svenjuergens/disable_beuser`

## Configuration
After installation you have the possibility to exclude single user from the scheduler task.

![EditUser](https://raw.github.com/SvenJuergens/disable_beuser/master/Documentation/Images/exclude-user.png)

Optional: You can set an individual HTML E-Mail Template in ExtensionManager Configuration

![configuration2](https://raw.github.com/SvenJuergens/disable_beuser/master/Documentation/Images/set-emailtemplate.png)

###Task Configuration 

**Input field: "Time of Inactivity to disable Beuser"**

Here you have to set a time span e.g. "1 months". You have to use a correkt  (PHP) Date/Time Format.

valid examples are:

+ 1 day
+ 1 week
+ 1 month
+ 1 year
+ 10 days
+ 10 weeks
+ 10 months
+ 10 years


invalid examples are:

+ ein Tag
+ 1 Woche
+ one months

Background:
The scheduler task create a Datetime Object and subtract the time span from "now".

**Input field: "Notification Email (optional) "**

If you set an email address you get the date and a list with disabled user from the task.
(Separate Mails with ";" )


![configuration3](https://raw.github.com/SvenJuergens/disable_beuser/master/Documentation/Images/disable-beuser-task.png)

**Input field: "TestRunner (optional) "**

with this field checked, you only make a test run and no user status where changed