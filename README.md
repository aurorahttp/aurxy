Aurxy
======

Http proxy server with filter.

Workflow
--------
 -------- request -------->         ----------- middleware -----------> 
|                         |        |                                  |
Client                   Aurxy Server                     Remote Server          
|                         |        |                                  |
 <------ response --------         <------------  filter <------------