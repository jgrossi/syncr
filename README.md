# Syncr

Syncr is a very simple PHP command to simplify the synchronization process between servers using PHP and MySQL. It's very useful to send and get files and MySQL databases for Wordpress projects, for example. With just one command you can send files and the entire database to your production server, and vice versa, updating your local server with the remote database and project files.

Basically the command synchronize files using rsync, both local to remote and remote to local. 

You can sync MySQL database too. The command create a SQL file, transfer between servers and import into MySQL database, removing the generated SQL file.

You can use Syncr with password support or using private keys. If you want to set the username `password` for your SSH connection you must set it on the configuration file. Otherwise let `password` empty to use private keys.

*This project is under development.*

## Installation

Just clone this repository inside your project root.

    git clone https://github.com/jgrossi/syncr.git .

You'll have the following files and folders:

- `public/`: directory where you should put your public files (PHP, CSS, JS, etc)
- `LICENCE`: licence file
- `README.md`: README file
- `syncr.json`: the configuration file (change it with your information)
- `syncr.php`: the PHP command to be executed

## Requirements

To be executed this command needs the following packages or commands:

- `rsync`: to sync files between servers using SSH
- `ssh`: to connect to the remote server
- `sshpass`: to send password using the command line with SSH command
- `mysqldump`: to create a backup of your MySQL database - remote or local
- `gzip`: to compress the SQL to improve the performance of the transfer process
- `scp`: to copy files between local and remote servers

## The configuration file `syncr.json`

Below you have a `syncr.json` sample file. Change the file content to your needs.

    {
        "remote": {
            "server": {
                "host": "example.com",
                "username": "user",
                "password": "secret",
                "ssh_port": 22,
                "path": "./example.com/public/"
            },
            "database": {
                "name": "example",
                "username": "user",
                "password": "secret"
            },
            "ignore_from_local": [
                "password.php", 
                "local.ini"
            ]
        },
        "local": {
            "server": {
                "path": "./public/"
            },
            "database": {
                "name": "example",
                "username": "user",
                "password": "secret"
            },
            "ignore_from_remote": [
                "*.ini", 
                "public/uploads/",
                "*.html"
            ]
        }
    }

## Synchronizing files between servers

Just run this command inside your root project to synchronizing files *from local to remote* server:

    php syncr.php --up

Synchronizing files *from remote to local* server:

    php syncr.php --down

### Ignoring files between the synchronization process

You can setup rules to ignore files when uploading or downloading. Just set the `ignore_from_local` and `ignore_from_remote` paramaters in the configuration file `syncr.json`. You can set rules like `*.ini`, `public/uploads/`, `password*`, etc.

## Synchronizing MySQL databases

Syncr can dump the local or remote MySQL database and import to your local or remote MySQL. Just include `--database` in the command:

    php syncr.php --up --database
    php syncr.php --down --database

## Global `syncr` command

You can create a global `syncr` command (Mac OSX and Linux). Just clone this repo inside some path like `~/Code/syncr` and create a symbolic link to `/usr/bin/syncr`:

    sudo ln -s ~/Code/syncr/syncr.php /usr/bin/syncr

After that you can just use the command inside your project root (remember you need the `syncr.json` config file), for example:

    syncr --up --database
