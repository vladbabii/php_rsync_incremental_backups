# Php-managed Rsync incremental backups

This is a minimal script that does incremental backups based on http://www.mikerubel.org/computers/rsync_snapshots/

The backup flow, when there is at least one backup done
- create new folder with timestamp
- hard link all files from the last backup
- copy with rsync only the difference

Space per backup taken: the total of modified file size.

It marks a backup as incomplete if it had rsync errors or if did not finish, and it does not make hardlinks based on it.

Read more at http://www.mikerubel.org/computers/rsync_snapshots/ if you're interested in what it does.

Configuration:
- timezone
- from - data to backup
- to - folder that containes all backups in sub-folders
- keep - how many snapshots to keep
- rounding - will create a backup every $rounding seconds in a folder with floor(time()/$rounding) format
- rsync - rsync executable path, can contain other paramters
- behind - how many backups to considr making a hardlink out of - too old and it will make a fresh backup

Dependencies: rsync needs to be installed, then just copy the php file, modify the first 10 to configure it, and run it or schedule it with cron.

Feel free to modify & use in any way.
