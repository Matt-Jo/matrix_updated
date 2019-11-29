var ck = ck || {};

ck.loader = function(opts) {
	// this will be a singleton
	this.opts = opts || {};
}

ck.loader.prototype.load = function(src, id, cb) {
	if (this.opts.script_folder) src = this.opts.script_folder+'/'+src;

	jQuery.ajax({
		url: src,
		dataType: 'script',
		success: function() {
			if (cb) cb(ck[id]);
		}
	});
}

ck.loader.prototype.get = function(fn, cb) {
	if (ck[fn]) return cb?cb(ck[fn]):ck[fn];
	else return this.load('ck-'+fn+'.max.js', fn, cb);
}