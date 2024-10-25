# lytix_planner

This plugin is a subplugin of [local_lytix](https://github.com/llttugraz/moodle-local_lytix);
it helps students and teachers to plan and organize events.

## Installation

1. Download the plugin and extract the files.
2. Move the extracted folder to your `moodle/local/lytix/modules/` directory.
3. Log in as an admin in Moodle and navigate to `Site Administration > Plugins > Install plugins`.
4. Follow the on-screen instructions to complete the installation.

## Requirements

- Moodle Version: 4.1+
- PHP Version: 7.4+
- Supported Databases: MariaDB, PostgreSQL
- Supported Moodle Themes: Boost

## Features

This plugin helps students and teachers to plan and organize events.

## Configuration

No settings for the subplugin are available.

## Usage

The provided widget of this subplugin is part of the LYTIX operation mode `Learner's Corner`. We refer to [local_lytix](https://github.com/llttugraz/moodle-local_lytix) for the configuration of this operation mode. If the mode `Learner's Corner` is active  and if a course is in the list of supported courses for this mode, then this widget is displayed when clicking on `Learner's Corner` in the main course view.

## API Documentation

No API.

## Privacy

The following personal data of each user are stored if the functionality of LYTIX for a course is enabled.

- In table `lytix_planner_milestone`:

    | Entry            | Description                  |
    |------------------|------------------------------|
    | userid           | The ID of the user           |
    | courseid         | The ID of the course         |
    | type             | Type of planner entry        |
    | marker           | Marker of milestone          |
    | startdate        | Date of planner entry        |
    | enddate          | Enddate of milestone         |
    | title            | Title of planner entry       |
    | text"            | Text of milestone            |
    | completed        | Is milestone completed?      |

- In table `lytix_planner_event_comp`:

    | Entry     | Description                         |
    |-----------|-------------------------------------|
    | userid    | The ID of the user                  |
    | courseid  | The ID of the course                |
    | eventid   | Id of the event                     |
    | completed | Is event completed?                 |
    | timestamp | Time the event was marked completed |


## Dependencies

- [local_lytix](https://github.com/llttugraz/moodle-local_lytix)
- [lytix_helper](https://github.com/llttugraz/moodle-lytix_helper)
- [lytix_logs](https://github.com/llttugraz/moodle-lytix_logs)

## License

This plugin is licensed under the [GNU GPL v3](https://github.com/llttugraz/moodle-lytix_planner?tab=GPL-3.0-1-ov-file).

## Contributors

- **Günther Moser** - Developer - [GitHub](https://github.com/ghinta)
