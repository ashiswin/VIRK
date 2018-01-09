package com.virk.redemptionappandroid;

import android.app.ProgressDialog;
import android.content.Intent;
import android.support.v7.app.AppCompatActivity;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.Button;
import android.widget.TextView;
import android.widget.Toast;

import com.android.volley.Request;
import com.android.volley.Response;
import com.android.volley.VolleyError;

import org.json.JSONException;
import org.json.JSONObject;

import java.util.HashMap;
import java.util.Map;

public class RedemptionActivity extends AppCompatActivity {
    private static final String TAG = "RedemptionActivity";

    private MainApplication application;

    private TextView txtReward;
    private Button btnRedeem;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_redemption);

        application = (MainApplication) getApplicationContext();

        txtReward = (TextView) findViewById(R.id.txtReward);
        btnRedeem = (Button) findViewById(R.id.btnRedeem);

        txtReward.setText(getIntent().getStringExtra("reward"));

        btnRedeem.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                // Display loading dialog
                final ProgressDialog dialog = new ProgressDialog(RedemptionActivity.this);
                dialog.setTitle("Redeeming Reward");
                dialog.setMessage("Please wait while your reward is redeemed");
                dialog.setIndeterminate(true);
                dialog.show();

                Map<String, String> redeemObject = new HashMap<>();
                redeemObject.put("eventid", getIntent().getLongExtra("eventid", 0) + "");
                redeemObject.put("studentid", getIntent().getStringExtra("studentid"));

                CustomRequest registerRequest = new CustomRequest(Request.Method.POST, MainApplication.SERVER_HOST + "/scripts/Redeem.php", redeemObject, new Response.Listener<JSONObject>() {
                    @Override
                    public void onResponse(JSONObject response) {
                        try {
                            if(response.getBoolean("success")) {
                                // Remove loading dialog
                                dialog.cancel();

                                Toast.makeText(RedemptionActivity.this, "Reward has been redeemed", Toast.LENGTH_SHORT).show();
                                finish();
                            }
                            else {
                                Log.d(TAG, response.getString("message"));
                                Toast.makeText(RedemptionActivity.this, response.getString("message"), Toast.LENGTH_SHORT).show();
                                finish();
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

                application.queue.add(registerRequest);
            }
        });
    }
}
