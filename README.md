PHPScheduler
============

A Scheduler / Task runner for PHP with plugable backends.
------------


Create tasks objects and schedule them to run somewhere else.
You can use PHPScheduler to schedule jobs to run in the future, or simply
schedule them to run right away.


Task storage is plugable, so you can choose where you want to store the tasks.
Currently, the following backends are planned (but you can easily add more):

### InMemory (done)
Useful for debugging and task buffering.

### File (done)
Running with a single server? No access to a database or Redis?
This one is for you. Goes nicely with a minutely cronjob for running the tasks.

### Redis
Use Redis as a task backend.
Useful if you have multiple servers (or task workers).

### MySQL
Store tasks in a MySQL table.
A lot slower than Redis, but gets the job done.

