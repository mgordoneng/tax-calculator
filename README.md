#Tax Calculator

This is an implementation of a script that will fill out a 2012 Social Security Worksheet(lines 20a and 20b) of Tax Form 1040. 

It can be ran by just executing it as a normal php script, and echos out display information to console. This data was made up by me for now, and can use more accurate sample data to test strange edge cases.

Part of the requirements given to me were that the code would require minimal changes to change logic and add steps as this form changes frequently. I chose to implement methods as closures and build them out in a data structure to represent the worksheet. State is maintained in TaxPayer Objects, and are independant of them form itself. Tax Payers can invoke steps via indirection characteristic of closure methods. This makes it pretty easy to throw in new functionality, or adjust existing functionality without large changes to most of the sourcecode -- just the areas effected. 

Steps are setup with unique id's, and dependencies are setup between the steps, and steps can be directly routed to a next specifec next step. The sequence itself is testable, as are transitions to each step, and the tax payer data that is consumed by this code. It is hard-coded now but a good idea would be to implement unit tests, and build this out to slurp data from the file system in a nice markup language like JSON/XML/YML for Tax Payer Data.