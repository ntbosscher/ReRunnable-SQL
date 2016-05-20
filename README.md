# ReRunnable-SQL

Takes SQL changes code and wraps each segment in IF EXISTS checks. See Notes for supported queries.

run ```php sql-changes.php```

Paste your sql code into the STDIN
Something like
```
	ALTER TABLE [dbo].[MyTBL] ADD CONSTRAINT [FK_MyTBL_AnotherTBL] FOREIGN KEY (
		[SomeID]
	) REFERENCES [dbo].[AnotherTBL] (
		[SomeID]
	);
	GO

	--- next query...

```


Enter a new line with only a period on it

A .sql file will open with the modified code.


#### Note

Curently setup for:

1. FK and PK constraints
2. DROP and CREATE Table


- Not tested on Windows
