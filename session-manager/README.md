# Suma Session Manager

**Suma Session Manager** is a tool for modifying data that has previously been submitted to **Suma: A Space Assessment Toolkit** (https://github.com/cazzerson/Suma). Suma is a space usage/statistics package; it natively does not allow users to delete or modify data once it has been submitted. This session manager allows the user to "delete" (actually hide from reporting) or to alter the timestamps on individual sessions. 

**Note:** The good folks who wrote **Suma** very deliberately elected not to offer functions that delete or alter the data once submitted. Suma Session Manager does all sorts of things they didn't intend. I respect their original decision making and offer this project for other Suma users who want more flexibility. Remember: with great power comes great responsibility. 

## System Requirements

**Suma Session Manager** is written in PHP, and uses PHP's PDO (PHP Data Objects) extension to connect with the MySQL database. It was developed and tested in a CentOS Linux/Apache environment; performance in other environments is as yet unknown. 

## Installation

Install the Suma Retroactive Data Importer in a folder *outside* of the main Suma web space. The prefered install method is to use: 
`git clone https://github.com/kenirwin/Suma-Session-Manager.git`

You can also download a zip file of the current version from: 
https://github.com/kenirwin/Suma-Session-Manager

Copy the **config-example.php** file to be **config.php**, then configure the **config.php** file with the MySQL connection information for your Suma Server as well as the Suma Server URL and Suma Reports URL. You may customize other variables as well. You may want to pay special attention to these variables:

`$one_per_hour_inits = array();` // this is an array of initiatives IDs for which you would generally prefer to have only one count per hour. 

`$default_init   = "";` // this takes a single initiative ID that will be the default initiative when the page loads. 

### Security

Suma Manager should be installed in a secure directory, as anyone with access to the page will be able to alter the information in your Suma database. Consult your system adminitstrator for the most sensible access restriction in your environment.

## Usage

Suma Session Manager will let the user select a Suma Initiative (or use the default initiative set in $default_init in config.php) and show the most recent 100 sessions (or the number of sessions set in $entries_per_page in config.php). The user can use the buttons at the top of the display to move to older/newer sessions chronologically, or use the "Select Any Date" button to select all sessions from a particular date. 

A table displays sessions from the time-period selected, including the start and end time of the session as well as the total number of counts in that session. For each session, the two right-hand columns offer tools for altering the session: Delete/Undelete and Adjust Time. 

### Delete/Undelete
The simplest function of Suma Session Manager is that it allows you to "Delete" sessions. "Deleting" a session does not remove it from the database, it simply marks the session as deleted and the session will not be included in activity reports. Once a session has been deleted, it may be "Undeleted" as well. The Delete and Undelete buttons appear on the right-hand side of each session in a list. 

### Adjust Time
The Adjust Time function allows the user to nudge the timestamp for the session and all of its associated counts forward or backward in time by a specified amount. The default array of times by which a session may be adjusted can be customized in the $adjust_time_options array in config.php. 

This function was developed with a particular purpose in mind. Suma can be used for reporting hourly head-counts. If two head-counts are conducted in the same hour, Suma will add both counts together in its hourly summary. If a user intends to collect a head-count once per hour, it may sometimes come to pass that, for example, the "8am-9am" headcount is performed at 8:05 and the "9am-10am" is performed at 8:55. If this occurs, the Adjust time function would allow the user to move the "9am-10am" count forward by 10 minutes so that it appears to have been performed at 9:05. Although this muddies the precision of the collection time, it also allows the statistical reporting to more closely reflect the conditions observed. It is suggested that the "Adjust Time" function will best serve users when (after the adjustment) both the "start" and "end" time for a session are in the same hour.

### Detecting Hours with Multiple Sessions

As noted above, some initiatives such as head-counts may be intended by Suma's users to collect one session per hour. Therefore, it may be useful to some users to detect occasions in which two sessions occur in the same hour of the same day and to adjust one or more of those sessions to keep the data in the intended hourly slots. To this end, the "Hours with Multiple Sessions" tab will appear for any initiative for which the initiative ID is included in the $one_per_hour_inits array in config.php. 

Clicking on DateHour link in the "Hours with Multiple Sessions" tab will bring up the full calendar day's entries for the selected date and initiative. The sessions corresponding to the DateHour link will be marked in red so the user may identify the relevant sessions and decide whether or not to adjust or delete one or more of the sessions.


## Demo

You can watch a brief video demonstration of the software on YouTube:
https://youtu.be/ULEQ0ImmWvI

## Credits and Invitation to Contribute

**Suma Session Manager** was developed by Ken Irwin (kirwin AT wittenberg DOT edu) at Wittenberg University and is an open-source project. Please feel free to contribute new features, issues, or bug-fixes on GitHub: https://github.com/kenirwin/Suma-Session-Manager

Users of **Suma Session Manager** may also be interestd in **Suma Retroactive Data Importer** project, which allows users to retroactively add counts to Suma: https://github.com/kenirwin/suma-retroactive-data-importer -- like the Session Manager, it works outside of Suma itself to contribute additional functionality. 

## Acknowledgements
   * Jason Casden & Bret Davidson are the project lead and technical lead for the original Suma project. Thanks for their help in understanding the underpinnings of their project enough to implement this add-on.

