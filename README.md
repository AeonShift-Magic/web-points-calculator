![readme-files/aeonshift-logo-transparent.png](readme-files/aeonshift-logo-transparent.png)

# AEONSHIFT POINTS CALCULATOR 💯

<table style="border: 0 solid transparent">
    <thead>
        <tr>
            <th>
                Repository Continuous Integration Health Task
            </th>
            <th>
                Task Status
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                PHP-CS-Fixer Code Style
            </td>
            <td>
                <img src="https://github.com/AeonShift-Magic/web-points-calculator/actions/workflows/phpcsfixer.yml/badge.svg" alt="PHP-CS-Fixer Badge">
            </td>
        </tr>
        <tr>
            <td>
                PHPStan Static Analysis
            </td>
           <td>
                <img src="https://github.com/AeonShift-Magic/web-points-calculator/actions/workflows/phpstan.yml/badge.svg" alt="PHPStan Badge">
           </td> 
        </tr>
        <tr>
            <td>
                Psalm Static Analysis
            </td>
            <td>
                <img src="https://github.com/AeonShift-Magic/web-points-calculator/actions/workflows/psalm.yml/badge.svg" alt="Psalm Badge">
            </td>
        </tr>
        <tr>
            <td>
                PHPUnit Tests
            </td>
            <td>
                <img src="https://github.com/AeonShift-Magic/web-points-calculator/actions/workflows/phpunit.yml/badge.svg" alt="PHP Unit Tests Badge">
            </td>
        </tr>
        <tr>
            <td>
                Composer Security Scan
            </td>
            <td>
                <img src="https://github.com/AeonShift-Magic/web-points-calculator/actions/workflows/composer-security.yml/badge.svg" alt="Composer Security Badge">
            </td>
        </tr>
        <tr>
            <td>
                ElectronJS Native App Build
            </td>
            <td>
                <img src="https://github.com/AeonShift-Magic/web-points-calculator/actions/workflows/electronjs.yml/badge.svg" alt="ElectronJS">
            </td>
        </tr>
    </tbody>
</table>

## PHP + JavaScript points calculator for Aeonshift

This codebase does several things:

- set up a dynamic website with full management of AeonShiftpoints calculator rules and releases,
- import Magic The Gathering™ cards from Scryfall bulk data files into a local database,
- provide dynamic a frontend interface to calculate AeonShiftpoints for decks of cards.
- provide a static export of the points calculator for offline use, as a standalone web page.
- provide an executable standalone version with ElectronJS/ElectronForge for Windows 7-11, MacOS 10.15+, and Linux.

This project uses vanilla JavaScript and [Symfony](https://symfony.com/)/PHP, and should mostly
work on MySQL/MariaDB DB servers (though PostGresSQL should work too).

This codebase is ready-to-use for Magic The Gathering™ cards, using [Scryfall API](https://scryfall.com/docs/api/cards) as source.  
The code is pluggable to be used with other sources of data, or licenses.

**Note: this project requires PHP 8.4+, Composer, Node 24, and Symfony basic knowledge to be set up.**

<hr>

### 💻 Setting up locally

Just do

```shell
composer install
``` 

...to install dependencies. This should trigger some recipes' commands you'll need to accept.

Then you should set up your `.env.local` file based on the `.env` template.

And create the database with:

```shell
php bin/console doctrine:database:create
php bin/console doctrine:schema:update --force
```

Then install assets using:

```shell
php bin/console assets:install
php bin/console importmap:install

# To prepare assets, you'll need TailwindCSS, hence the executable
php bin/console tailwind:build # production, static assets
# or
php bin/console tailwind:build --watch --poll # development, dynamic assets, live reload from file changes
```

Then you can run a local PHP server with:

```shell
symfony server:start -d
```

...or use Docker and build this inside containers:

- Apache 2 / NginX
- PHP 8.4+
- MySQL 8.0+ / MariaDB 10.6+

You can access the application at `http://localhost:8000` (or another port if 8000 is already used).

### Principles

This application has a backend in PHP/Symfony, and a frontend in vanilla JavaScript.
Admins should connect to the backend to do some maintenance tasks:

- Get the file from Scryfall (the app is only provided with a Scryfall file parser,
  you'll need to create yours if you use another source of data for cards).
- Import the Scryfall data from the downloaded local file into the database for cards.
- Create a release when new rules are published, using a points calculator model in case it would change.

You can locally generate the ElectronJS app with the provided commands:

```shell
# For MTG Electron app builds, locally
npm run mtg-electron-build
# Or as a file watcher for development
npm run mtg-electron-start
```

### 🔍 Code Quality

Locally, you can run:

```shell
./vendor/bin/php-cs-fixer fix --diff --dry-run
./vendor/bin/phpstan analyse src tests
./vendor/bin/psalm
./vendor/bin/phpunit
```

Or, on Windows, you can use the shortcut batch files (named for one-letter quick access):

```shell
.\csfixer.bat # PHP-CS-Fixer code quality check, make sure your IDE doesn't auto-format code differently
.\psalm.bat # Psalm static analysis, max level, must return no error
.\stan.bat # PHPStan static analysis, max level, must return no error
.\unit.bat # PHPUnit tests, must return all tests passed correctly

# Or, all at once to do a final pre-flight check before pushing:
.\allchecks.bat
```

**PHPStorm/IntelliJ Users:** the attached and versioned `phpstorm-settings.zip` folder contains pre-configured settings
in sync with the code quality tools used in this project.
You can import these settings in your IDE to have the same code style, inspections, and quality tools setup.
Just create a new IDE settings profile, and import the zip file contents into it: 
`File > Manage IDE Settings > Import Settings...`.

You can build the ElectronJS app with:

```shell
.\electronjs.bat
```

**If any of these commands fail, please fix the issues before pushing code.**  
Check your Github Actions CI results for more details.

### 📡 Deployment

Feel free to edit and reuse the `production-deployment.sh` script to deploy your own instance of this application.
The provided script works for servers with SSH access.
This script is intended to be used on a platform that doesn't provide Node runtime.

Don't forget to also set up your local `.env.local` file on the production server.

This codebase doesn't use Doctrine Migrations.
Yet, the first time you set this up on a new machine, you can run:

```shell
php bin/console doctrine:database:create
php bin/console doctrine:schema:update --force
```

You can also generate the static assets for production with:

```shell
php bin/console aeonshift:mtg:builder:staticassets:v1
```

This is used to generate the standalone builds for ElectronJS too.  
You can build the ElectronJS/ElectronForge app for production with:

```shell
# On Windows
.\electronjs.bat
```
Or simply use ElectronForge directly if you have Node installed:

```shell
# For MTG Electron app builds
npm run mtg-electron-make
```

### Usage

#### Using the embedded native appliction

EletroncJS and Electronforge generate native applications for Windows, MacOS, and Linux.
You can download the latest releases from the [Releases section](https://github.com/AeonShift-Magic/web-points-calculator/releases/tag/latest).

The native applications are NOT digitally signed, which means they'll trigger some security warnings on some OSes.
You can safely bypass these warnings as this software is open-source and free of malware.

#### Using [Scryfall "Default Cards" bulk JSON file](https://scryfall.com/docs/api/bulk-data) as source:

To have stuff displayed on the frontend, you need to:

1. Import Scryfall bulk file for cards.
2. Parse the cards from the Scryfall file into the database.
3. Create a release for the points calculator, choosing a model.

Steps 1 and 2 need to be done each time you want to update the card database.
They can be automated. Using a cron job is a good idea, calling the console commands below:

This will limit the use with cards in English only (base reference for judging Magic The Gathering™ cards).

```shell
php bin/console aeonshift:mtg:updatedb:scryfalldefaultmtgcards:v1 # to download the Scryfall file
php bin/console aeonshift:mtg:sourcedownload:scryfalldefaultmtgcards:v1 # to update from the Scryfall file if present
```

*(remember doing this too often will have your server banned if abusive, make sure it is done reasonably)*  
*(note: the commands are only in plain English for now, no localization available)*

### Other Considerations

#### Adding your initial admin user

Use the console to hash your password:

```shell
php bin/console security:hash-password
```

Then, run the following SQL query to insert your admin user into the `as_user` table.

```sql
INSERT INTO as_user (
    email, 
    password, 
    registered_at, 
    roles, 
    username, 
    created_at, 
    updated_at
) 
VALUES (
    'admin@example.com',
    'YOUR_HASHED_PASSWORD',
    NOW(),
    '["ROLE_ADMIN"]',
    'admin',
    NOW(),
    NOW()
);
```

#### Official Colors

Triad-based color set for branding:
```shell
# Neutral tones
#151515 / #4E4E4E / #888888
#FFFFFF

# Tonality tones
#0085D7 / #0073BB / #00619D
#F5F200 / #BBB700 / #9D9A00
#DBD848 / #A09E35 / #84822B
#CD6666 / #B05858 / #753B3B 
```

<hr>

This software is distributed under the MIT License.

*This software and the generated websites are not affiliated nor endorsed by Wizards of the Coast™.  
The name Magic The Gathering™, the Magic The Gathering™ logo, 
the illustrations and some materials here are the property of Wizards of the Coast™. ©Wizards of the Coast™ LLC. 
This project follows Wizards Of The Coast™ fansite policy.*


✨ Original code from [William Pinaud (DocFX)](https://github.com/DocFX) for AeonShift, 2025.

With help from lovely contributors:
- [Buisson](https://github.com/Buisson)
  
Feel free to contribute to this project, reuse it, share it, star it on GitHub if you like it, or donate if you like and can!
