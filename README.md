<p align="center"><img src="https://raw.githubusercontent.com/panlatent/aurxy/master/docs/images/logo.png" 
alt="aurxy" /></p>

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