package com.virk.votingapp;

import android.app.Application;

import com.android.volley.RequestQueue;

/**
 * Created by ashis on 6/12/2017.
 */

public class MainApplication extends Application {
    public static final String SERVER_HOST = "http://10.1.1.176/virk";
    RequestQueue queue;

    String reward;
    int minVotes;
}
