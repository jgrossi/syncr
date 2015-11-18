# Syncr

Syncr is a very simple PHP command to simplify the synchronization process between servers using PHP and MySQL.

- *This project is under development.*
- *Missing tests sending SSH password. For now working only with public key already installed.*

## Installation

Just clone this repository inside your project root.

    git clone https://github.com/jgrossi/syncr.git .

You'll have 2 files:

- `syncr.json`: the configuration file
- `syncr.php`: the PHP command to be executed

## Requirements

To be executed this command needs the following packages or commands:

- `rsync`
- `ssh`
- `sshpass`
- `mysqldump`
- `gzip`
- `scp`

## The configuration file `syncr.json`

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
                "password": "secret",
                "host": "localhost"
            }
        },
        "local": {
            "server": {
                "path": "./public/"
            },
            "database": {
                "name": "example",
                "username": "user",
                "password": "secret",
                "host": "localhost"
            }    
        }
    }

## Synchronizing files between servers

Just run this command inside your root project to synchronizing files *from local to remote* server:

    php syncr.php --up

Synchronizing files *from remote to local* server:

    php syncr.php --down

## Synchronizing MySQL databases

Just include `--database` in the command:

    php syncr.php --up --database
    php syncr.php --down --database


