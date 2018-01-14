Overview
========

The reporting module is used to gather information from other modules into a consolidated report that
can be generated into HTML or PDF and emailed to the admins of the business.

Reports can be set to run daily or weekly.

Each module that has information for reports contains a directory 'reporting' which must have
a blocks.php and block.php. The blocks.php will return a list of available report blocks to
this module. When a report is run, each block in the report will call block.php in the other
module to get the block data.

When a block is run, it returns an array of chunks, which are used in sequence in the report. This 
allows for a single block to return multiple pieces of data, so it can add a paragraph, chart and table 
to the report, each being their own chunk.
