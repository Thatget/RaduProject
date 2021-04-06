var config = {
    paths: {
        revolutionTools:        'Nwdthemes_Revslider/public/assets/js/rbtools.min',
        rs6:                    'Nwdthemes_Revslider/public/assets/js/rs6.min',
        vimeoPlayer:            'Nwdthemes_Revslider/public/assets/js/vimeo.player.min'
    },
    shim: {
        rs6: {
            deps: ['jquery', 'revolutionTools']
        },
		revolutionTools: {
			deps: ['jquery']
		}
    }
};