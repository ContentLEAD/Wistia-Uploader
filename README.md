Wistia-Uploader
===============

A script that when called will upload videos from the Brafton video feed into a designated Wistia account using the Wistia API.

A few notes:

-Wistia API Key is required.  The user for the API, per wistia, is always API so I don't ask for that.

-Wistia Project ID is recommended.  Uncommenting the list_projects call at the beginning will, surprisingly, list the projects.  If there are no projects or none is desired, leave the constant blank but still declared and it will function as intended. 

-Wistia currently lacks anything in the way of a reference ID so the only Wistia-side check is by title.  Am currently toying with a client side (brafton server) database to help with this, as clients are pretty likely to change the name.

-The script uploads the HDmp4 or mp4 version of the video.  It will not upload if neither of these is present.

-This script assumes the CName is set up.  If it is not, a simple string replace with the cloudfront link to replace the cname will solve the issue.

