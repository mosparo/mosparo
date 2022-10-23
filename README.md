&nbsp;
<p align="center">
    <img src="https://github.com/mosparo/mosparo/blob/master/assets/images/mosparo-logo.svg?raw=true" alt="mosparo logo contains a bird with the name Mo and the mosparo text"/>
</p>

<p align="center">
    The <b>mo</b>dern <b>spa</b>m p<b>ro</b>tection tool.<br>
    It replaces other captcha methods with a simple and easy to use spam protection solution.<br>
    <em>FYI: The bird is called <a href="https://mothesparrow.com" target="_blank">Mo (the sparrow)</a>.</em>
</p>

-----

## Description

mosparo is the modern solution to protect your online forms from spam. The protection method is pretty simple: mosparo 
blocks spam based on rules which match the data from the form. The detection method is comparable with an email spam filter.
The user does not have to proof that it is a real human by solving a puzzle. Instead, the tool scans the entered form data
for words or other informations which are not allowed. You can add different kind of rules to catch all possible spam.

### How it works
Spam filters are common on email servers. There they scan a whole message to detect a possible spam message. Additionally,
a lot of settings can prevent spam mails (or at least make them better visible) like SPF, DKIM and so on. But the hard part: 
the email is one message and the spam filter has to check the full message. Since everything is together in one message, it
can lead to false detection.

In web forms, the solution is easier: since all fields are seperated, we can check all fields separately. In one of the fields,
the spam bot has to write in example the URL to the website or the message. Because of that we can execute our checks for
the field and detect spam very easy - if there is a rule to detect spam.

### Our target

We don't guarantee you, that mosparo will catch all your spam messages since the detection is mainly based on your rules. If
you set up enough rules we estimate that more than 80% of the spam messages will be blocked.

Our main objective is a different one. Firstly, we wanted to create a solution that you can host on your own server or 
web hosting and that does not collect as much data as possible.

When we looked for ways to do that, we found that there wasn't a real solution for everyone. Many existing solutions 
require a puzzle that the user must resolve. For people with disabilities, solving puzzles is maybe not a good way to prove 
that they are real people.

We have therefore defined our main objective: to collect only data that is necessary, self-hosted and accessible.

## Key features

- mosparo only uses the data which the user has entered in the form, the IP address of the user and the user agent of the browser but does not collect other data
  - All user data are encrypted by default 
  - All collected data are deleted in a fixed time interval. All data will be deleted after 14 days (maybe 15 days because of cronjob execution and so on)
- Usable for everybody: the mosparo spam protection method does not use puzzles or obscured images to protected the form.
- Open source and self-hosted
- The checkbox is customizable in the size and the color *(work in progress)*

## Requirements
- PHP 7.4 or newer (**Important:** If you have PHP 8.1, you need at least 8.1.10)
- PHP extensions
  - `ctype`
  - `iconv`
  - `intl`
  - `json`
  - `pdo`
  - `pdo_mysql`
  - `openssl`
  - `zip`
  - `posix` *(optional)*
  - `sodium` *(optional)*
  - `Zend OPcache` *(optional)*
- A MySQL database (MySQL or MariaDB)
- Less than 100 MB disk space
- A domain or subdomain

## Installation

### Archived package
The installation is very easy. There are different installation method but the main method is to use the zip archive:
1. Download the latest release from [our website](https://mosparo.io) or from the [releases page](https://github.com/mosparo/mosparo/releases) on GitHub.
2. Extract the file
3. Create a new web host in your hosting control panel (like a new subdomain)
   1. If possible, point the document root of the web host to the subdirectory 'public'
4. Upload all the files in the extracted directory to this new virtual host
5. Open your browser and access the virtual host (in example by accessing the subdomain in your browser)
6. Follow the installation wizard to install mosparo

### From source
To install mosparo from source, clone the repository and execute Composer and Yarn in order to install the dependencies.

#### Requirements
- git
- [Composer](https://getcomposer.org/)
- [Yarn](https://yarnpkg.com/)

#### Installation
1. Clone the repository
    ```bash
    git clone git@github.com:mosparo/mosparo.git
    ```
2. Switch into the repository directory
    ```bash
    cd mosparo
    ```
3. Execute composer
    ```bash
    composer install
    ```
4. Install the yarn dependencies
    ```bash
    yarn install
    ```
5. Build the frontend resources
    ```bash
    yarn encore dev
    ```

## License
mosparo is open-sourced software licensed under the [MIT License](https://opensource.org/licenses/MIT).

Please see the [LICENSE](LICENSE) file for the full license.

## Contributing

See [CONTRIBUTING](CONTRIBUTING.md)