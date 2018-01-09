package com.virk.redemptionappandroid;

import android.app.DownloadManager;
import android.app.ProgressDialog;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.DialogInterface;
import android.content.Intent;
import android.content.IntentFilter;
import android.content.SharedPreferences;
import android.database.Cursor;
import android.net.ConnectivityManager;
import android.net.NetworkInfo;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.os.Environment;
import android.preference.PreferenceManager;
import android.support.v7.app.AlertDialog;
import android.support.v7.app.AppCompatActivity;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.BaseAdapter;
import android.widget.Button;
import android.widget.Spinner;
import android.widget.TextView;
import android.widget.Toast;

import com.android.volley.Request;
import com.android.volley.Response;
import com.android.volley.VolleyError;
import com.android.volley.toolbox.StringRequest;
import com.android.volley.toolbox.Volley;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

public class MainActivity extends AppCompatActivity {
    private static final String TAG = "MainActivity";

    private long updateDownloadReference;
    private MainApplication application;

    // Handles to UI elements
    private Spinner spnEvent;
    private Button btnLogin;

    // Data provider for events spinner
    private EventsAdapter eventsAdapter;

    // String to hold server's APK MD5 hash
    String newMD5;
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        // Get handle to application context
        application = (MainApplication) getApplicationContext();

        // Get handles to UI elements
        spnEvent = (Spinner) findViewById(R.id.spnEvent);
        btnLogin = (Button) findViewById(R.id.btnLogin);

        // Set up Volley request queue
        application.queue = Volley.newRequestQueue(this);

        // Check for internet connection
        if(!isNetworkAvailable()) {
            Toast.makeText(this, "Please connect to the internet and restart the app", Toast.LENGTH_LONG).show();
            finish();
            return;
        }

        // Check for updates
        checkForUpdate();

        // Load events
        loadEvents();

        // Set listener for login button
        btnLogin.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                // Launch WaitingActivity with the selected event
                long event = eventsAdapter.getItemId(spnEvent.getSelectedItemPosition());
                Intent waitingIntent = new Intent(MainActivity.this, WaitingActivity.class);
                waitingIntent.putExtra("eventid", event);

                startActivity(waitingIntent);
                finish();
            }
        });
    }

    /* loadEvents(): This method is used to load all events
     * from the server.
     */
    private void loadEvents() {
        // Display loading dialog
        final ProgressDialog dialog = new ProgressDialog(MainActivity.this);
        dialog.setTitle("Loading Events");
        dialog.setMessage("Please wait while we load available events");
        dialog.setIndeterminate(true);
        dialog.show();

        StringRequest eventRequest = new StringRequest(Request.Method.GET, MainApplication.SERVER_HOST + "/scripts/GetEvents.php",
                new Response.Listener<String>() {
                    @Override
                    public void onResponse(String response) {
                        try {
                            JSONObject result = new JSONObject(response);

                            // Remove loading dialog
                            dialog.cancel();

                            // Check for errors
                            if(result.getBoolean("success")) {
                                // Load data into spinner
                                eventsAdapter = new EventsAdapter(result.getJSONArray("events"));
                                spnEvent.setAdapter(eventsAdapter);
                            }
                            else {
                                // Display error message from server
                                Toast.makeText(MainActivity.this, result.getString("message"), Toast.LENGTH_SHORT).show();
                            }
                        } catch (JSONException e) {
                            e.printStackTrace();
                        }
                    }
                }, new Response.ErrorListener() {
            @Override
            public void onErrorResponse(VolleyError error) {
                System.out.println("Something went wrong!");
                error.printStackTrace();
            }
        });

        application.queue.add(eventRequest);
    }

    /* checkForUpdate(): This method is used to check for updates to the app
     * by comparing its current MD5 hash with the version on the server
     */
    public void checkForUpdate() {
        StringRequest updateRequest = new StringRequest(Request.Method.GET, MainApplication.SERVER_HOST + "/scripts/CheckAppUpdate.php?appName=redemption-app.apk",
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

                                // Check for a match between the MD5 hashes
                                if(!md5.equals(currentmd5)) {
                                    // Display a dialog prompting user to update
                                    Log.d(TAG, "Update available!");
                                    AlertDialog.Builder builder = new AlertDialog.Builder(MainActivity.this);
                                    builder.setTitle("Update available");
                                    builder.setCancelable(false);
                                    builder.setMessage("Would you like to download the updated version of Virk Redeem?");
                                    builder.setPositiveButton("Yes", new DialogInterface.OnClickListener() {
                                        @Override
                                        public void onClick(DialogInterface dialog, int which) {
                                            // Launch DownloadManager to handle the downloading of the APK file
                                            DownloadManager downloadManager = (DownloadManager)getSystemService(DOWNLOAD_SERVICE);
                                            Uri Download_Uri = Uri.parse(MainApplication.SERVER_HOST + "/bin/redemption-app.apk");
                                            DownloadManager.Request request = new DownloadManager.Request(Download_Uri);
                                            request.setAllowedNetworkTypes(DownloadManager.Request.NETWORK_WIFI);
                                            request.setAllowedOverRoaming(false);
                                            request.setTitle("Virk Redeem Download");
                                            request.setDestinationInExternalFilesDir(MainActivity.this, Environment.DIRECTORY_DOWNLOADS,"redemption-app.apk");
                                            updateDownloadReference = downloadManager.enqueue(request);
                                            newMD5 = md5;
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
                            // Newer versions require more secure content:// URI to install
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

                            // Save the new file's MD5 for future checks
                            SharedPreferences preferences = PreferenceManager.getDefaultSharedPreferences(MainActivity.this);
                            SharedPreferences.Editor editor = preferences.edit();

                            editor.putString("md5", newMD5);
                            editor.apply();
                        }
                    }
                }
            }
        };
        // Register the broadcast receiver
        IntentFilter filter = new IntentFilter(DownloadManager.ACTION_DOWNLOAD_COMPLETE);
        registerReceiver(downloadReceiver, filter);

        // Launch request
        application.queue.add(updateRequest);
    }

    /* isNetworkAvailable(): This method is used to check if we are connected
     * to the internet.
     */
    private boolean isNetworkAvailable() {
        ConnectivityManager connectivityManager
                = (ConnectivityManager) getSystemService(Context.CONNECTIVITY_SERVICE);
        NetworkInfo activeNetworkInfo = connectivityManager.getActiveNetworkInfo();
        return activeNetworkInfo != null && activeNetworkInfo.isConnected();
    }

    /*
     * The EventsAdapter class handles the interfacing with the dropdown
     * spinner by loading the data from the provider and populating
     * the dropdown UI
     *
     * @author  Isaac Ashwin
     * @version 1.0
     * @since   2017-6-20
     */
    private class EventsAdapter extends BaseAdapter {
        final JSONArray data;
        EventsAdapter(JSONArray data) {
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
                e.printStackTrace();
                return 0;
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
