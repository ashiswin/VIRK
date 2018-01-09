package com.virk.votingapp.utils;

import android.content.Context;
import android.util.DisplayMetrics;

/**
 * Created by ashis on 6/24/2017.
 */

public class Utils {
    public static int dpToPx(Context context, int dp) {
        DisplayMetrics displayMetrics = context.getResources().getDisplayMetrics();
        return Math.round(dp * (displayMetrics.xdpi / DisplayMetrics.DENSITY_DEFAULT));
    }
}
