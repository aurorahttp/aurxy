<p align="center"><img src="https://raw.githubusercontent.com/panlatent/aurxy/master/docs/images/logo.png" 
alt="aurxy" /></p>

HTTP proxy server with powerful customizable filter rules.

Workflow
--------

```
   ------ request  ---- filter --->        ---------------> 
  |                                |      |                |
Client       Aurxy Server         middleware         Remote Server  
  |                                |      |                |
   <----- response ---- filter ----        <---------------
 ```