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

## Now in open beta!

**mosparo is finally available in open beta. Please report to us possible bugs as an issue.**

## Description

mosparo is the modern solution to protect your online forms from spam. The protection method is simple: mosparo blocks spam based on rules matching the form's data. The detection method is comparable to an email spam filter. The user does not have to prove it is a real human by solving a puzzle. Instead, the tool scans the entered form data for words or other information which are not allowed. You can add different kinds of rules to catch all possible spam.

## How it works

Spam filters are standard on email servers. There they scan a whole message to detect a possible spam message. Additionally, many settings can prevent spam mail (or at least make them better visible), like SPF, DKIM, etc. But the hard part is that the email is one message, and the spam filter must check the entire message. Since everything is together in one message, it can lead to false detection.

In web forms, the solution is more straightforward: since all fields are separated, we can check all fields separately. In one of the fields, the spam bot has to write, for example, the URL to the website or the message. Because of that, we can execute our checks for the field and detect spam very quickly - if there is a rule to detect spam.

## Our target

We don't guarantee that mosparo will catch all your spam messages since the detection is mainly based on your rules. If you set up enough rules, we estimate that mosparo will block more than 80% of the spam messages.

Our main objective is a different one. Firstly, we wanted to create a solution that you can host on your server or web hosting that does not collect as much data as possible.

When we looked for ways to do that, we found that there wasn't a real solution for everyone. Many existing solutions require a puzzle that the user must resolve. For people with disabilities, solving puzzles is maybe not a good way to prove that they are real people.

We have therefore defined our main objective: to collect only data that is necessary, self-hosted, and accessible.

## Key features

- mosparo only uses the data which the user has entered in the form, the IP address of the user, and the user agent of the browser but does not collect other data
  - All user data are encrypted by default
  - All collected data are deleted in a fixed time interval. All data will be deleted after 14 days (maybe 15 days because of cronjob execution and so on)
- Usable for everybody: the mosparo spam protection method does not use puzzles or obscured images to protect the form. 
- Open-source and self-hosted 
- The checkbox is customizable in size and the color

## Requirements

- PHP 7.4 or newer (Important: If you have PHP 8.1, you need at least 8.1.10)
- PHP extensions
  - ctype
  - iconv
  - intl
  - json
  - pdo
  - pdo_mysql
  - openssl
  - zip
  - posix (optional)
  - sodium (optional)
  - Zend OPcache (optional)
- A MySQL database (MySQL or MariaDB)
- Less than 100 MB of disk space
- A domain or subdomain

## Installation

### Archived package

The installation is straightforward. There are different installation methods, but the primary method is to use the zip archive:

1. Download the latest release from our website or the releases page on GitHub.
2. Extract the file
3. Create a new web host in your hosting control panel (like a new subdomain)
   1. If possible, point the document root of the web host to the subdirectory 'public'
4. Upload all the files in the extracted directory to this new virtual host
5. Open your browser and access the virtual host (for example, by accessing the subdomain in your browser)
6. Follow the installation wizard to install mosparo

### From source

To install mosparo from the source, clone the repository and execute Composer and Yarn to install the dependencies.

#### Requirements

- git
- Composer
- Yarn

#### Installation

1. Clone the repository
```bash
git clone git@github.com:mosparo/mosparo.git
```
2. Switch to the repository directory
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
6. Open your browser and access the virtual host (for example, by accessing the subdomain in your browser)
7. Follow the installation wizard to install mosparo

## Documentation

You can find our documentation here: https://documentation.mosparo.io

## License

mosparo is open-sourced software licensed under the [MIT License](https://opensource.org/licenses/MIT).
Please see the [LICENSE](LICENSE) file for the full license.

## Contributing

See [CONTRIBUTING](CONTRIBUTING.md)

### Translate mosparo

We're using [Weblate](https://weblate.org/) to translate mosparo. If you want to help translate mosparo, please head over to our Weblate project: https://hosted.weblate.org/projects/mosparo/

Thank you for helping to make mosparo better.

