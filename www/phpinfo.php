<?php 
$text = '3705,3609,3570,3566,3510,3408,3401,3380,3379,3355,3260,3251,3007,2868,2840,2827,2818,2769,2699,2635,2556,2551,2467,2466,2465,2464,2463,2462,2461,2460,3022,2992,2960,2919,2823,2816,2815,2675,2674,2669,2668,2666,2665,2662,2661,2660,2659,2658,2656,3519,3518,3253,3252,3245,3244,3228,3216,3214,3213,3208,3206,2868,3364,3337,3270,3269,3264,3263,3239,3238,3198,3169,3141,3131,3076,3064,2982,2976,2974,2954,2953,2948,2922,2820,2819,2810,2809,2807,2806,2805,2802,2800,2799,2798,2797,2796,2794,2793,2792,2791,2790,2789,2787,2786,2785,2784,2783,2782,2781,2769,2654,2648,2612,2478,2475,2467,2466,2465,2464,2463,2462,2461,2460,1902,1899,954,3806,3796,3749,3737,3733,3722,3662,3605,3598,3501,3466,3461,3459,3457,3456,3455,3454,3452,3451,3450,3449,3448,3447,3446,3445,3402,3341,3001,3608,3436,3354,3353,3352,3351,3340,3283,3277,3251,3250,3249,3248,3247,3147,3029,3021,3020,3019,3018,3014,3007,3006,3005,3004,3003,3002,3000,2997,2994,2989,2987,2986,2985,2935,2934,2921,2918,2908,2853,2851,2840,2827,2824,2814,2773,2699,2683,2635,2556,2551,2519,2518,2505,2503,2502,2501,2500,2492,2352,3931,3904,3764,3740,3697,3695,3686,3685,3655,3653,3621,3583,3562,3561,3547,3502,3476,3475,3428,3387,2499,3274,3266,3066,3057,3050,2845,2844,2843,2842,2841,2838,2837,2818,2698,2695,2492,2352,3787,3700,3692,3648,3647,3523,3517,3479,3476,3428,3422,3421,3365,3362,3317,3301,3223,3201,2862,2861,2860,2859,2858,2857,2856,2846,2767,2499,685,3324,3323,3318,3025,2932,2872,2867,2866,2825,2780,2752,2751,2749,2679,2643,2642,3696,3645,3529,3441,3417,3416,3359,3273,3272,2822,3938,3881,3755,3622,3620,3569,3568,3567,3545,3544,3542,3541,3540,3539,3530,3519,3518,3465,3424,3238,2882,3260,3546,3548,2933,3226,3100';
$text1 = '685,954,1899,1902,2352,2460,2461,2462,2463,2464,2465,2466,2467,2475,2478,2492,2499,2500,2501,2502,2503,2505,2518,2519,2551,2556,2612,2635,2642,2643,2648,2654,2656,2658,2659,2660,2661,2662,2665,2666,2668,2669,2674,2675,2679,2683,2695,2698,2699,2749,2751,2752,2767,2769,2773,2780,2781,2782,2783,2784,2785,2786,2787,2789,2790,2791,2792,2793,2794,2796,2797,2798,2799,2800,2802,2805,2806,2807,2809,2810,2814,2815,2816,2818,2819,2820,2822,2823,2824,2825,2827,2837,2838,2840,2841,2842,2843,2844,2845,2846,2851,2853,2856,2857,2858,2859,2860,2861,2862,2866,2867,2868,2872,2882,2908,2918,2919,2921,2922,2932,2934,2935,2948,2953,2954,2960,2974,2976,2982,2985,2986,2987,2989,2992,2994,2997,3000,3001,3002,3003,3004,3005,3006,3007,3014,3018,3019,3020,3021,3022,3025,3029,3050,3057,3064,3066,3076,3131,3141,3147,3169,3198,3201,3206,3208,3213,3214,3216,3223,3228,3238,3239,3244,3245,3247,3248,3249,3250,3251,3252,3253,3260,3263,3264,3266,3269,3270,3272,3273,3274,3277,3283,3301,3317,3318,3323,3324,3337,3340,3341,3351,3352,3353,3354,3355,3359,3362,3364,3365,3379,3380,3387,3401,3402,3408,3416,3417,3421,3422,3424,3428,3436,3441,3445,3446,3447,3448,3449,3450,3451,3452,3454,3455,3456,3457,3459,3461,3465,3466,3475,3476,3479,3501,3502,3510,3517,3518,3519,3523,3529,3530,3539,3540,3541,3542,3544,3545,3547,3561,3562,3566,3567,3568,3569,3570,3583,3598,3605,3608,3609,3620,3621,3622,3645,3647,3648,3653,3655,3662,3685,3686,3692,3695,3696,3697,3700,3705,3722,3733,3737,3740,3749,3755,3764,3787,3796,3806,3881,3904,3931,3938';
$arr = array_unique(explode(',', $text));
$arr1 = array_unique(explode(',', $text1));
$diff = array_diff($arr, $arr1);
$diff = implode(',', $diff);
var_dump($diff);die;