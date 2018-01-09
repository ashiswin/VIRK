package com.virk.registrationappandroid;

import android.Manifest;
import android.app.DownloadManager;
import android.app.PendingIntent;
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
import android.nfc.NfcAdapter;
import android.nfc.Tag;
import android.nfc.tech.IsoDep;
import android.nfc.tech.NfcB;
import android.os.Build;
import android.os.Bundle;
import android.os.Environment;
import android.preference.PreferenceManager;
import android.support.v4.app.ActivityCompat;
import android.support.v4.content.ContextCompat;
import android.support.v7.app.AlertDialog;
import android.support.v7.app.AppCompatActivity;
import android.util.Log;
import android.view.View;
import android.view.WindowManager;
import android.widget.Button;
import android.widget.TextView;
import android.widget.Toast;

import com.android.volley.Request;
import com.android.volley.Response;
import com.android.volley.VolleyError;
import com.android.volley.toolbox.StringRequest;
import com.android.volley.toolbox.Volley;
import com.virk.registrationappandroid.utils.Acr3x;
import com.virk.registrationappandroid.utils.Acr3xNotifListener;

import org.json.JSONException;
import org.json.JSONObject;

public class MainActivity extends AppCompatActivity {
    private static final String MIME_TEXT_PLAIN = "text/plain";
    private static String TAG = "MainActivity";
    public static final int REGISTRATION_INTENT = 0;
    public static final int PERMISSIONS_REQUEST_RECORD_AUDIO = 1;

    private long updateDownloadReference;
    private MainApplication application;
    private NfcAdapter mNfcAdapter;

    Acr3x acr3x;
    Acr3xNotifListener listener;

    private TextView txtReward;
    private Button btnLogout;

    String newMD5;

    boolean activityCovered = false;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        getWindow().addFlags(WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON);

        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        application = (MainApplication) getApplicationContext();
        application.queue = Volley.newRequestQueue(this);

        btnLogout = (Button) findViewById(R.id.btnLogout);

        // Check for internet connection
        if(!isNetworkAvailable()) {
            Toast.makeText(this, "Please connect to the internet and restart the app", Toast.LENGTH_LONG).show();
            finish();
            return;
        }

        checkForUpdate();

        btnLogout.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                AlertDialog.Builder logoutBuilder = new AlertDialog.Builder(MainActivity.this);
                logoutBuilder.setTitle("Logout of VIRK Register");
                logoutBuilder.setMessage("Would you like to log out now?");

                logoutBuilder.setCancelable(true);
                logoutBuilder.setPositiveButton("Ok", new DialogInterface.OnClickListener() {
                    @Override
                    public void onClick(DialogInterface dialog, int which) {
                        if(acr3x != null) {
                            acr3x.stop();
                        }
                        finish();
                    }
                });
                logoutBuilder.setNegativeButton("Cancel", new DialogInterface.OnClickListener() {
                    @Override
                    public void onClick(DialogInterface dialog, int which) {
                        dialog.cancel();
                    }
                });
                logoutBuilder.show();
            }
        });

        mNfcAdapter = NfcAdapter.getDefaultAdapter(this);

        if (mNfcAdapter == null) {
            Log.d(TAG, "Failed to find NFC adapter. Falling back to ACR35");
            AudioManager manager = (AudioManager)getSystemService(Context.AUDIO_SERVICE);
            if(!manager.isWiredHeadsetOn()) {
                Toast.makeText(MainActivity.this, "Please connect the ACR35 NFC Reader and restart the app", Toast.LENGTH_LONG).show();
                finish();
                return;
            }
            // Fall back to ACR35
            testACR();
            return;
        }

        if (!mNfcAdapter.isEnabled()) {
            Toast.makeText(this, "NFC is disabled.", Toast.LENGTH_SHORT).show();
        }
    }

    @Override
    protected void onResume() {
        super.onResume();

        setupForegroundDispatch(mNfcAdapter);
    }

    @Override
    protected void onPause() {
        stopForegroundDispatch(mNfcAdapter);

        super.onPause();
    }

    @Override
    public void onBackPressed() {
    }

    @Override
    protected void onNewIntent(Intent intent) {
        String action = intent.getAction();

        if (NfcAdapter.ACTION_TECH_DISCOVERED.equals(action)) {
            Tag tag = intent.getParcelableExtra(NfcAdapter.EXTRA_TAG);
            String tagId = bytesToHex(tag.getId());

            Intent redemptionIntent = new Intent(MainActivity.this, RegistrationActivity.class);
            redemptionIntent.putExtra("tagid", tagId);
            startActivityForResult(redemptionIntent, REGISTRATION_INTENT);
        }
        else {
            finish();
        }
    }

    final protected static char[] hexArray = {'0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F'};
    public static String bytesToHex(byte[] bytes) {
        char[] hexChars = new char[bytes.length * 3 - 1];
        int v;
        for ( int j = 0; j < bytes.length; j++ ) {
            v = bytes[j] & 0xFF;
            hexChars[j * 3] = hexArray[v >>> 4];
            hexChars[j * 3 + 1] = hexArray[v & 0x0F];
            if(j != bytes.length - 1) {
                hexChars[j * 3 + 2] = ':';
            }
        }
        return new String(hexChars);
    }

    public void setupForegroundDispatch(NfcAdapter adapter) {
        if(adapter == null) {
            return;
        }

        final Intent intent = new Intent(this, getClass());
        intent.setFlags(Intent.FLAG_ACTIVITY_SINGLE_TOP);

        final PendingIntent pendingIntent = PendingIntent.getActivity(getApplicationContext(), 0, intent, 0);

        IntentFilter[] filters = new IntentFilter[1];
        String[][] techList = new String[][]{ new String[] {NfcB.class.getName(), IsoDep.class.getName()}};

        // Notice that this is the same filter as in our manifest.
        filters[0] = new IntentFilter();
        filters[0].addAction(NfcAdapter.ACTION_TECH_DISCOVERED);
        filters[0].addCategory(Intent.CATEGORY_DEFAULT);
        try {
            filters[0].addDataType(MIME_TEXT_PLAIN);
        } catch (IntentFilter.MalformedMimeTypeException e) {
            throw new RuntimeException("Check your mime type.");
        }

        adapter.enableForegroundDispatch(this, pendingIntent, filters, techList);
    }

    public void stopForegroundDispatch(NfcAdapter adapter) {
        if(adapter == null) {
            return;
        }
        adapter.disableForegroundDispatch(this);
    }

    @Override
    protected void onDestroy() {
        if(acr3x != null) {
            acr3x.stop();
        }
        super.onDestroy();
    }

    public void testACR() {
        // Assume thisActivity is the current activity
        int permissionCheck = ContextCompat.checkSelfPermission(this, Manifest.permission.RECORD_AUDIO);
        if(permissionCheck !=  PackageManager.PERMISSION_GRANTED) {
            Log.d(TAG, "Permission missing");
            ActivityCompat.requestPermissions(this,
                    new String[]{Manifest.permission.RECORD_AUDIO},
                    PERMISSIONS_REQUEST_RECORD_AUDIO);
        }
        else {
            Log.d(TAG, "Permission available");

            AudioManager manager = (AudioManager) getSystemService(Context.AUDIO_SERVICE);
            acr3x = new Acr3x(manager);
            listener = new Acr3xNotifListener() {
                @Override
                public void onUUIDAavailable(String uuid) {
                    if(!uuid.equals("0x6300") && !activityCovered) {
                        String baseUUID = uuid.substring(2, uuid.length());
                        final StringBuilder sb = new StringBuilder();
                        for(int i = 0; i < baseUUID.length(); i++) {
                            sb.append(baseUUID.charAt(i));
                            if((i + 1) % 2 == 0 && i != baseUUID.length() - 1) {
                                sb.append(":");
                            }
                        }
                        Log.d(TAG, sb.toString());
                        runOnUiThread(new Runnable() {
                            @Override
                            public void run() {
                                String tagId = sb.toString();

                                Intent redemptionIntent = new Intent(MainActivity.this, RegistrationActivity.class);
                                redemptionIntent.putExtra("tagid", tagId);
                                startActivityForResult(redemptionIntent, REGISTRATION_INTENT);
                                activityCovered = true;
                            }
                        });
                    }
                }

                @Override
                public void onFirmwareVersionAvailable(String firmwareVersion) {
                    Log.d(TAG, firmwareVersion);
                }
            };

            acr3x.start(listener);
            Log.d(TAG, "STARTED");
        }
    }

    @Override
    public void onRequestPermissionsResult(int requestCode,
                                           String permissions[], int[] grantResults) {
        switch (requestCode) {
            case PERMISSIONS_REQUEST_RECORD_AUDIO: {
                // If request is cancelled, the result arrays are empty.
                if (grantResults.length > 0
                        && grantResults[0] == PackageManager.PERMISSION_GRANTED) {
                    testACR();
                    // permission was granted, yay! Do the
                    // contacts-related task you need to do.

                } else {
                    Toast.makeText(this, "Audio recording permission required", Toast.LENGTH_LONG).show();
                }
                return;
            }
        }
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, Intent data) {
        if(requestCode == REGISTRATION_INTENT) {
            activityCovered = false;
            //acr3x.read(listener);
        }
    }
    public void checkForUpdate() {
        StringRequest updateRequest = new StringRequest(Request.Method.GET, MainApplication.SERVER_HOST + "/scripts/CheckAppUpdate.php?appName=registration-app.apk",
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
                                    builder.setMessage("Would you like to download the updated version of Virk Register?");
                                    builder.setPositiveButton("Yes", new DialogInterface.OnClickListener() {
                                        @Override
                                        public void onClick(DialogInterface dialog, int which) {
                                            DownloadManager downloadManager = (DownloadManager)getSystemService(DOWNLOAD_SERVICE);
                                            Uri Download_Uri = Uri.parse(MainApplication.SERVER_HOST + "/bin/registration-app.apk");
                                            DownloadManager.Request request = new DownloadManager.Request(Download_Uri);
                                            request.setAllowedNetworkTypes(DownloadManager.Request.NETWORK_WIFI);
                                            request.setAllowedOverRoaming(false);
                                            request.setTitle("Virk Register Download");
                                            request.setDestinationInExternalFilesDir(MainActivity.this, Environment.DIRECTORY_DOWNLOADS,"registration-app.apk");
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
}
