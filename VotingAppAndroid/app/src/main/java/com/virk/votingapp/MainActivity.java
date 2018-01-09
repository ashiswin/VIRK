package com.virk.votingapp;

/*
 * The MainActivity class handles the login for groups
 * registering for the exhibition
 *
 * @author  Isaac Ashwin
 * @version 1.0
 * @since   2017-6-20
 */

import android.Manifest;
import android.app.DownloadManager;
import android.app.ProgressDialog;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.DialogInterface;
import android.content.Intent;
import android.content.IntentFilter;
import android.content.SharedPreferences;
import android.content.pm.PackageManager;
import android.database.Cursor;
import android.media.AudioManager;
import android.net.ConnectivityManager;
import android.net.NetworkInfo;
import android.net.Uri;
import android.os.Build;
import android.os.Environment;
import android.preference.PreferenceManager;
import android.support.v4.app.ActivityCompat;
import android.support.v4.content.ContextCompat;
import android.support.v7.app.AlertDialog;
import android.support.v7.app.AppCompatActivity;
import android.os.Bundle;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.AdapterView;
import android.widget.BaseAdapter;
import android.widget.Button;
import android.widget.EditText;
import android.widget.Spinner;
import android.widget.TextView;
import android.widget.Toast;

import com.acs.audiojack.AudioJackReader;
import com.android.volley.Request;
import com.android.volley.Response;
import com.android.volley.VolleyError;
import com.android.volley.toolbox.StringRequest;
import com.android.volley.toolbox.Volley;
import com.virk.votingapp.utils.Acr3x;
import com.virk.votingapp.utils.Acr3xNotifListener;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.io.File;
import java.util.HashMap;
import java.util.Map;

public class MainActivity extends AppCompatActivity {
    private static final String TAG = "MainActivity"; // Tag for logging

    private long updateDownloadReference;
    private MainApplication application;

    // Handles to UI elements
    private Spinner spnEvent, spnGroup;
    private EditText txtPassword;
    private Button btnLogin;

    // Adapters to provide data to dropdown spinners
    private VirkAdapter eventsAdapter, groupsAdapter;

    String newMD5;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        // Get access to global application object
        application = (MainApplication) getApplicationContext();

        // Get handles to UI elements
        spnEvent = (Spinner) findViewById(R.id.spnEvent);
        spnGroup = (Spinner) findViewById(R.id.spnGroup);
        txtPassword = (EditText) findViewById(R.id.txtPassword);
        btnLogin = (Button) findViewById(R.id.btnLogin);

        // Create a request queue for all internet requests
        application.queue = Volley.newRequestQueue(this);

        // Check for internet connection
        if(!isNetworkAvailable()) {
            Toast.makeText(this, "Please connect to the internet and restart the app", Toast.LENGTH_LONG).show();
            finish();
            return;
        }
        // Check for updates of the app
        checkForUpdate();

        // Load events from server
        loadEvents();

        // Listen for selection of event
        spnEvent.setOnItemSelectedListener(new AdapterView.OnItemSelectedListener() {
            @Override
            public void onItemSelected(AdapterView<?> parent, View view, int position, long id) {
                // Load appropriate group list from selected event
                loadGroups(id);
            }

            @Override
            public void onNothingSelected(AdapterView<?> parent) {
                // Do nothing
            }
        });

        // Listen for login button click
        btnLogin.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                // Get input values
                final long event = eventsAdapter.getItemId(spnEvent.getSelectedItemPosition());
                final long group = groupsAdapter.getItemId(spnGroup.getSelectedItemPosition());
                String password = txtPassword.getText().toString();

                // Ensure no empty password is transmitted
                if(password.isEmpty()) {
                    txtPassword.setError("Please enter your password");
                    return;
                }

                // Set up object for POST request
                Map<String, String> registerObject = new HashMap<>();
                registerObject.put("eventid", event + "");
                registerObject.put("groupid", group + "");
                registerObject.put("password", password);

                // Display progress dialog to user so they don't stare at static screen
                final ProgressDialog dialog = new ProgressDialog(MainActivity.this);
                dialog.setTitle("Logging in");
                dialog.setMessage("Please wait");
                dialog.setIndeterminate(true);
                dialog.setCancelable(false);
                dialog.show();

                // Create request object to register the group
                CustomRequest registerRequest = new CustomRequest(Request.Method.POST, MainApplication.SERVER_HOST + "/scripts/RegisterGroup.php", registerObject, new Response.Listener<JSONObject>() {
                    @Override
                    public void onResponse(JSONObject response) {
                        try {
                            // Remove login dialog
                            dialog.cancel();

                            // Check response from server
                            if(response.getBoolean("success")) {
                                // Prepare data to pass to WaitingActivity
                                Intent waitingIntent = new Intent(MainActivity.this, WaitingActivity.class);
                                waitingIntent.putExtra("eventid", event);
                                waitingIntent.putExtra("groupid", group);
                                waitingIntent.putExtra("groupname", response.getString("groupname"));

                                // Store event data in global application object to reduce server hits
                                application.reward = response.getJSONObject("event").getString("reward");
                                application.minVotes = response.getJSONObject("event").getInt("min");

                                // Launch WaitingActivity and close current activity
                                dialog.cancel();
                                startActivity(waitingIntent);
                                finish();
                            }
                            else {
                                // Display any server-side error to the user for rectification
                                txtPassword.setError(response.getString("message"));
                                Log.d(TAG, response.getString("message"));
                            }
                        } catch (JSONException e) {
                            e.printStackTrace();
                        }
                    }
                }, new Response.ErrorListener() {
                    @Override
                    public void onErrorResponse(VolleyError error) {
                        error.printStackTrace();
                    }
                });

                // Launch registration request
                application.queue.add(registerRequest);
            }
        });
    }

    /* loadEvents(): This method is used to load all events
     * from the server.
     */
    private void loadEvents() {
        // Display a loading message while loading events
        final ProgressDialog dialog = new ProgressDialog(MainActivity.this);
        dialog.setTitle("Loading Events");
        dialog.setMessage("Please wait while we load available events");
        dialog.setIndeterminate(true);
        dialog.show();

        // Create a new GET request for all events available
        StringRequest eventRequest = new StringRequest(Request.Method.GET, MainApplication.SERVER_HOST + "/scripts/GetEvents.php",
                new Response.Listener<String>() {
                    @Override
                    public void onResponse(String response) {
                        try {
                            // Convert response to a JSON object for usage
                            JSONObject result = new JSONObject(response);

                            // Remove loading dialog
                            dialog.cancel();

                            // Check for a successful response from server
                            if(result.getBoolean("success")) {
                                // Add events to the dropdown spinner in the UI
                                eventsAdapter = new VirkAdapter(result.getJSONArray("events"));
                                spnEvent.setAdapter(eventsAdapter);
                            }
                            else {
                                // Display any error message to the user for rectification
                                Toast.makeText(MainActivity.this, result.getString("message"), Toast.LENGTH_SHORT).show();
                            }
                        } catch (JSONException e) {
                            e.printStackTrace();
                        }
                    }
                }, new Response.ErrorListener() {
            @Override
            public void onErrorResponse(VolleyError error) {
                error.printStackTrace();
            }
        });

        // Launch request
        application.queue.add(eventRequest);
    }

    /* loadGroups(long id): This method loads all groups that are
     * associated with an event from the server.
     */
    private void loadGroups(long id) {
        // Display a loading message while loading groups
        final ProgressDialog dialog = new ProgressDialog(MainActivity.this);
        dialog.setTitle("Loading Groups");
        dialog.setMessage("Please wait while we load available groups");
        dialog.setIndeterminate(true);
        dialog.show();

        // Create a new GET request to get all groups based on an event id
        StringRequest groupRequest = new StringRequest(Request.Method.GET, MainApplication.SERVER_HOST + "/scripts/GetGroups.php?id=" + id,
                new Response.Listener<String>() {
                    @Override
                    public void onResponse(String response) {
                        try {
                            // Convert response to JSON object for easier use
                            JSONObject result = new JSONObject(response);

                            // Remove loading dialog
                            dialog.cancel();

                            // Check for a successful resposne from the server
                            if(result.getBoolean("success")) {
                                // Add the groups to the dropdown spinner and enable it
                                groupsAdapter = new VirkAdapter(result.getJSONArray("groups"));
                                spnGroup.setAdapter(groupsAdapter);
                                spnGroup.setClickable(true);
                            }
                            else {
                                // Display any errors to the user for their rectification
                                Toast.makeText(MainActivity.this, result.getString("message"), Toast.LENGTH_SHORT).show();
                            }
                        } catch (JSONException e) {
                            e.printStackTrace();
                        }
                    }
                }, new Response.ErrorListener() {
            @Override
            public void onErrorResponse(VolleyError error) {
                error.printStackTrace();
            }
        });

        // Launch request
        application.queue.add(groupRequest);
    }

    public void checkForUpdate() {
        StringRequest updateRequest = new StringRequest(Request.Method.GET, MainApplication.SERVER_HOST + "/scripts/CheckAppUpdate.php?appName=voting-app.apk",
                new Response.Listener<String>() {
                    @Override
                    public void onResponse(String response) {
                        try {
                            // Convert response to JSON object for easier use
                            JSONObject result = new JSONObject(response);


                            // Check for a successful response from the server
                            if(result.getBoolean("success")) {
                                final String md5 = result.getString("md5");

                                SharedPreferences preferences = PreferenceManager.getDefaultSharedPreferences(MainActivity.this);
                                String currentmd5 = preferences.getString("md5", "null");

                                if(!md5.equals(currentmd5)) {
                                    Log.d(TAG, "Update available!");
                                    AlertDialog.Builder builder = new AlertDialog.Builder(MainActivity.this);
                                    builder.setTitle("Update available");
                                    builder.setCancelable(false);
                                    builder.setMessage("Would you like to download the updated version of Virk Voter?");
                                    builder.setPositiveButton("Yes", new DialogInterface.OnClickListener() {
                                        @Override
                                        public void onClick(DialogInterface dialog, int which) {
                                            DownloadManager downloadManager = (DownloadManager)getSystemService(DOWNLOAD_SERVICE);
                                            Uri Download_Uri = Uri.parse(MainApplication.SERVER_HOST + "/bin/voting-app.apk");
                                            DownloadManager.Request request = new DownloadManager.Request(Download_Uri);
                                            request.setAllowedNetworkTypes(DownloadManager.Request.NETWORK_WIFI);
                                            request.setAllowedOverRoaming(false);
                                            request.setTitle("Virk Voter Download");
                                            request.setDestinationInExternalFilesDir(MainActivity.this, Environment.DIRECTORY_DOWNLOADS,"voting-app.apk");
                                            updateDownloadReference = downloadManager.enqueue(request);
                                            newMD5 = md5;
                                            Log.d(TAG, "Download started with reference " + updateDownloadReference);
                                        }
                                    }).setNegativeButton("No", new DialogInterface.OnClickListener() {
                                        @Override
                                        public void onClick(DialogInterface dialog, int which) {
                                            dialog.cancel();
                                        }
                                    });

                                    builder.show();
                                }
                            }
                            else {
                                // Display any errors to the user for their rectification
                                Toast.makeText(MainActivity.this, result.getString("message"), Toast.LENGTH_SHORT).show();
                            }
                        } catch (JSONException e) {
                            e.printStackTrace();
                        }
                    }
                }, new Response.ErrorListener() {
            @Override
            public void onErrorResponse(VolleyError error) {
                error.printStackTrace();
            }
        });

        //broadcast receiver to get notification about ongoing downloads
        BroadcastReceiver downloadReceiver = new BroadcastReceiver() {

            @Override
            public void onReceive(Context context, Intent intent) {

                //check if the broadcast message is for our Enqueued download
                long referenceId = intent.getLongExtra(DownloadManager.EXTRA_DOWNLOAD_ID, -1);
                if(updateDownloadReference == referenceId){
                    Log.v(TAG, "Reference status " + updateDownloadReference + " lel");
                    DownloadManager dm = (DownloadManager) getSystemService(DOWNLOAD_SERVICE);
                    DownloadManager.Query query = new DownloadManager.Query();
                    query.setFilterById(updateDownloadReference);
                    Cursor c = dm.query(query);
                    if (c.moveToFirst()) {
                        int columnIndex = c.getColumnIndex(DownloadManager.COLUMN_STATUS);
                        if (DownloadManager.STATUS_SUCCESSFUL == c.getInt(columnIndex)) {
                            Log.v(TAG, "Downloading of the new app version complete");
                            //start the installation of the latest version
                            Uri apkUri;
                            if (android.os.Build.VERSION.SDK_INT >= Build.VERSION_CODES.N) {
                                apkUri = dm.getUriForDownloadedFile(updateDownloadReference);
                            }
                            else {
                                String uriString = c.getString(c.getColumnIndex(DownloadManager.COLUMN_LOCAL_URI));
                                apkUri = Uri.parse(uriString);
                            }

                            Intent installIntent = new Intent(Intent.ACTION_VIEW);
                            installIntent.setDataAndType(apkUri,
                                    "application/vnd.android.package-archive");
                            Log.e(TAG, "Opening " + apkUri);
                            installIntent.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
                            installIntent.addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION);
                            startActivity(installIntent);

                            SharedPreferences preferences = PreferenceManager.getDefaultSharedPreferences(MainActivity.this);
                            SharedPreferences.Editor editor = preferences.edit();

                            editor.putString("md5", newMD5);
                            editor.apply();
                        }
                    }
                }
            }
        };
        IntentFilter filter = new IntentFilter(DownloadManager.ACTION_DOWNLOAD_COMPLETE);
        registerReceiver(downloadReceiver, filter);

        // Launch request
        application.queue.add(updateRequest);
    }

    private boolean isNetworkAvailable() {
        ConnectivityManager connectivityManager
                = (ConnectivityManager) getSystemService(Context.CONNECTIVITY_SERVICE);
        NetworkInfo activeNetworkInfo = connectivityManager.getActiveNetworkInfo();
        return activeNetworkInfo != null && activeNetworkInfo.isConnected();
    }

    /*
     * The VirkAdapter class handles the interfacing with the dropdown
     * spinner by loading the data from the provider and populating
     * the dropdown UI
     *
     * @author  Isaac Ashwin
     * @version 1.0
     * @since   2017-6-20
     */

    private class VirkAdapter extends BaseAdapter {
        final JSONArray data;
        VirkAdapter(JSONArray data) {
            this.data = data;
        }

        @Override
        public int getCount() {
            return data.length();
        }

        @Override
        public Object getItem(int position) {
            try {
                return data.get(position);
            } catch (JSONException e) {
                e.printStackTrace();
                return null;
            }
        }

        @Override
        public long getItemId(int position) {
            try {
                return ((JSONObject) getItem(position)).getInt("id");
            } catch (JSONException e) {
                try {
                    return ((JSONObject) getItem(position)).getInt("groupid");
                } catch (JSONException ex) {
                    ex.printStackTrace();
                    return 0;
                }
            }
        }

        @Override
        public View getView(int position, View convertView, ViewGroup parent) {
            View itemView;
            JSONObject event = (JSONObject) getItem(position);

            if(convertView == null) {
                LayoutInflater inflater = (LayoutInflater) MainActivity.this.getSystemService(LAYOUT_INFLATER_SERVICE);
                itemView = inflater.inflate(android.R.layout.simple_dropdown_item_1line, parent, false);
            }
            else {
                itemView = convertView;
            }

            TextView txtItem = (TextView) itemView.findViewById(android.R.id.text1);
            try {
                txtItem.setText(event.getString("name"));
            } catch (JSONException e) {
                e.printStackTrace();
            }

            return itemView;
        }
    }
}
