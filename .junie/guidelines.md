See [../.ai/guidelines.md](../.ai/guidelines.md) for project guidelines.

## Specific instructions for Junie
 - Never run any command directly in the terminal. Always use the justfile commands to ensure everything is run inside the docker container.
 - run `just test-unit` to run all unit tests, if you want to run unit tests for a specific file add the file at the end of the command.
 - write as few comments as possible. if the code is self-explanatory,