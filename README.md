![readme-files/logo-duel-commander-transparent.png](readme-files/Aeonshift-Logo-Transparent.png)

<table style="border: 0 solid transparent">
    <thead>
        <tr>
            <th>
                Continuous Integration Task
            </th>
            <th>
                Task Status
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                PHPStan Static Analysis
            </td>
           <td>
                <img src="https://github.com/AeonShift-Magic/web-points-calculator/actions/workflows/phpstan.yml/badge.svg" alt="PHPStan">
           </td> 
        </tr>
        <tr>
            <td>
                Psalm Static Analysis
            </td>
            <td>
                <img src="https://github.com/AeonShift-Magic/web-points-calculator/actions/workflows/psalm.yml/badge.svg" alt="Psalm">
            </td>
        </tr>
        <tr>
            <td>
                PHP-CS-Fixer Code Style
            </td>
            <td>
                <img src="https://github.com/AeonShift-Magic/web-points-calculator/actions/workflows/phpcsfixer.yml/badge.svg" alt="PHP-CS-Fixer">
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




# AEONSHIFT POINTS CALCULATOR 💯

## PHP + JavaScript points calculator for Aeonshift

This codebase does several things:

- set up a dynamic website with full management of Aeonshift points calculator rules and releases,
- import Magic The Gathering™ cards from Scryfall bulk data files into a local database,
- provide dynamic a frontend interface to calculate Aeonshift points for decks of cards.
- provide a static export of the points calculator for offline use, as a standalone web page.
- provide an executable standalone version with ElectronJS for Windows 7-11, MacOS 10.15+, and Linux.

This project uses vanilla JavaScript and [Symfony](https://symfony.com/)/PHP, and should mostly
work on MySQL/MariaDB DB servers (though PostGresSQL should work too).

This codebase is ready-to-use for Magic The Gathering™ cards, using [Scryfall API](https://scryfall.com/docs/api/cards) as source.  
The code is pluggable to be used with other sources of data, or licenses.

**Note: this project requires PHP 8.4+, Composer, and Symfony basic knowledge to be set up.**

### Setting up locally

Just do

```bash
composer install
``` 

...to install dependencies. This should trigger some recipes' commands you'll need to accept.

Then you should set up your `.env.local` file based on the `.env` template.

And create the database with:

```bash
php bin/console doctrine:database:create
php bin/console doctrine:schema:update --force
```

Then install assets using:

```bash
php bin/console assets:install
php bin/console importmap:install

# To prepare assets, you'll need TailwindCSS, hence the executable
php bin/console tailwind:build # production, static assets
# or
php bin/console tailwind:build --watch --poll # development, dynamic assets, live reload from file changes
```

Then you can run a local PHP server with:

```bash
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

### Deployment

Feel free to edit and reuse the `production-deployment.sh` script to deploy your own instance of this application.
This script is intended to be used on a platform that doesn't provide Node runtime.

Don't forget to also set up your local `.env.local` file on the production server.

This codebase doesn't use Doctrine Migrations.
Yet, the first time you set this up on a new machine, you can run:

```bash
php bin/console doctrine:database:create
php bin/console doctrine:schema:update --force
```

### Usage

To have stuff displayed on the frontend, you need to:

1. Import Scryfall bulk file for cards.
2. Parse the cards from the Scryfall file into the database.
3. Create a release for the points calculator, choosing a model.

Steps 1 and 2 need to be done each time you want to update the card database.
They can be automated. Using a cron job is a good idea, calling the console commands:

#### Using [Scryfall "Default Cards" bulk JSON file](https://scryfall.com/docs/api/bulk-data) as source:

This will limit the use with cards in English only (base reference for judging Magic The Gathering™ cards).

```bash
php bin/console aeonshift:updatedb:scryfalldefaultcards # to download the Scryfall file
php bin/console aeonshift:sourcedownload:scryfalldefaultcards # to update from the Scryfall file if present
```

*(remember doing this too often will have your server banned if abusive, make sure it is done reasonably)*  
*(note: the commands are only in plain English for now, no localization available)*

This software is distributed under the MIT License.
Feel free to use it as much as you can, and to contribute to it, PRs are welcome!

Original code from [William Pinaud (DocFX)](https://github.com/DocFX) for AeonShift, 2025.
