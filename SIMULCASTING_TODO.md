1. add the default.mp4 file and the default.font file to the docker utils
    Rewrite docker to copy those files to the path /var/azuracast/storage/simulcast/
    Update the ansible installer as well

2. maybe broadcast a message on simulcast stop event so it shown to the user even when the user is not in the simulcasting page

3. error mechanism is rather dirty since liquidsoap consider stopping the output on certain conditions as an error:
Avutil.Error(Container closed!)

4. i want to add a block to the dashboard/profile page to show the status of the simulcast instances however this will require a constant pull requests to the server which can be heavy, trying to think of alternatives.