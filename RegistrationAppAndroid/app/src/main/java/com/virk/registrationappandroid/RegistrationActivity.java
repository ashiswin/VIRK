package com.virk.registrationappandroid;

import android.app.DownloadManager;
import android.app.ProgressDialog;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.DialogInterface;
import android.content.Intent;
import android.content.IntentFilter;
import android.content.SharedPreferences;
import android.net.Uri;
import android.os.Environment;
import android.preference.PreferenceManager;
import android.support.v7.app.AlertDialog;
import android.support.v7.app.AppCompatActivity;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.Toast;

import com.android.volley.Request;
import com.android.volley.Response;
import com.android.volley.VolleyError;
import com.android.volley.toolbox.StringRequest;

import org.json.JSONException;
import org.json.JSONObject;

import java.util.HashMap;
import java.util.Map;

public class RegistrationActivity extends AppCompatActivity {
    private static String TAG = "RegistrationActivity";

    MainApplication application;

    EditText edtStudentId;
    Button btnLink;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_registration);

        application = (MainApplication) getApplicationContext();

        edtStudentId = (EditText) findViewById(R.id.edtStudentId);
        btnLink = (Button) findViewById(R.id.btnLink);

        btnLink.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                String studentId = edtStudentId.getText().toString();

                final ProgressDialog dialog = new ProgressDialog(RegistrationActivity.this);
                dialog.setTitle("Registering");
                dialog.setMessage("Please wait while your ID is registered");
                dialog.setCancelable(false);
                dialog.setIndeterminate(true);
                dialog.show();

                if(studentId.length() != 5 && studentId.length() != 7) {
                    edtStudentId.setError("Invalid student/staff ID entered");
                    dialog.cancel();
                    return;
                }

                Map<String, String> voteObject = new HashMap<>();
                voteObject.put("studentid", studentId);
                voteObject.put("tagid", getIntent().getStringExtra("tagid"));

                CustomRequest voteRequest = new CustomRequest(Request.Method.POST, MainApplication.SERVER_HOST + "/scripts/RegisterTag.php", voteObject, new Response.Listener<JSONObject>() {
                    @Override
                    public void onResponse(JSONObject response) {
                        dialog.cancel();
                        try {
                            if(response.getBoolean("success")) {
                                Toast.makeText(RegistrationActivity.this, "This tag has been successfully linked!", Toast.LENGTH_LONG).show();
                                finish();
                            }
                            else {
                                Toast.makeText(RegistrationActivity.this, response.getString("message"), Toast.LENGTH_LONG).show();
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

                application.queue.add(voteRequest);
            }
        });
    }
}
