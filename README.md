Aurxy
======

Http proxy server with filter.

Workflow
--------

```
   ------ request  ---- filter --->        ---------------> 
  |                                |      |                |
Client       Aurxy Server         middleware         Remote Server  
  |                                |      |                |
   <----- response ---- filter ----        <---------------
 ```