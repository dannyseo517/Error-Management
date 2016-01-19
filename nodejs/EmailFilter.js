/* necessary npms and intialization */
var mongodb = require('mongodb');
var nodemailer = require('nodemailer');
var smtpPool = require('nodemailer-smtp-pool');
var file = "users.db";
var fs = require("fs");
var MongoClient = mongodb.MongoClient;

/* email sender account */
var USER = 'bcit-mongo@gravit-e.ca';
var PASSWORD = 'zrKWHHrMAUX1RuNh';

/* timers */
var EXPIRE_SECONDS 	= 5;		// change to 900 seconds	(15 mins)
var ERROR_LIFESPAN 	= 10;		// change to 600 000 ms	(10 mins)
var POLL_RANGE		= 10;		// change to 30 (30 sec)
var POLL_INTERVAL	= 10000;	// change to 30 000 (30 sec)

/* define database here */
var main = 'mongodb://localhost:27017/test_grav';
var main_collection = 'graverr';
/* temporary database */
var checker = 'mongodb://localhost:27017/emailChecker';
var checker_collection = 'errors';

function userPref(doc, occur)
{
	var exists = fs.existsSync(file);

	if(!exists)
	{
		console.log("Cannot find database. Please check path and/or if db has been created");
		process.exit()
	}

	var sqlite3 = require("sqlite3").verbose();
	var db = new sqlite3.Database(file);

	//for each user in user table, check for the preference under the domain name.
	db.each("SELECT user_id as userid, user_email, mute FROM users", function(err, row1)
	{
		if(row1.mute==0)
		{
			db.get("SELECT up.user_id, users.user_email, up.prod_error, up.hprod_error, up.warning"
				+ " FROM userPreference up"
				+ " INNER JOIN users ON up.user_id = users.user_id" 
				+ " WHERE up.user_id = "+ row1.userid +" AND domain_name = \""+doc.DomainName+"\" ", function(err, row2) {
				
				//if preference was not found, check if the domain is globally muted.
				if(!row2)
				{
					db.get("SELECT * FROM domain WHERE domain_name = \""+doc.DomainName+"\" ", function(err, row3){
						//if there is no match with domain name under the domain table, send the email.

						if(!row3)
						{
							console.log("Send email to " + row1.user_email + ". Domain not muted");
							sendEmail(doc.EmailSubject, doc.EmailMessage, row1.user_email, occur);
						}
						//if the domain name is muted, then do not send emaild
						else if (row3.hprod_mute == 1 && doc.HandledException )
						{
							console.log("don't send email. This domain is muted (hprod)");
						}
						else if (row3.prod_mute == 1 && !doc.HandledException && doc.ErrorType != "Warning")
						{
							console.log("don't send email. This domain is muted(prod)");
						}
						else if (row3.warning_mute == 1 && doc.ErrorType == "Warning")
						{
							console.log("WARNING don't send email. This domain is muted");
						}
						//domain mute was set to 0 so don't send email
						else
						{
							console.log("Send email to " + row1.user_email + ". Domain not muted");
							sendEmail(doc.EmailSubject, doc.EmailMessage, row1.user_email, occur);
						}
					});
				}
				//preference was found under requested domain name
				else
				{
					var tempDisable = row2.temp_disable;
					var prodError = row2.prod_error;
					var hprodError = row2.hprod_error;
					var warning = row2.warning;
					
					//if the user is not temporarily muted, don't send email

					/* Exceptions */
					if(prodError == 0 && !doc.HandledException) // prod error
					{
						console.log("Send email to " + row2.user_email + ". send email(prod)");
						sendEmail(doc.EmailSubject, doc.EmailMessage, row2.user_email, occur);
					}
					else if(hprodError == 0 && doc.HandledException) // hprod error
					{
						console.log("Send email to " + row2.user_email + ". send email (hprod)");
						sendEmail(doc.EmailSubject, doc.EmailMessage,row2.user_email, occur);
					}
					/* Warning */
					else if(warning == 0 && doc.ErrorType == "Warning")
					{
						console.log("Send email to " + row2.user_email + ". send email (warning)");
						sendEmail(doc.EmailSubject, doc.EmailMessage, row2.user_email, occur);
					}
					else
					{
						console.log("dont't send email, user does not want this error");
					}
				}
			});
		}
		else
		{
			console.log("this user is muted");
		}
	});
	
}

/* emailer script */
function sendEmail(subj, msg, to_, occur)
{

	var transporter = nodemailer.createTransport(smtpPool({
		host: 'mail.gravit-e.ca',
		port: 587,
		auth:
		{
			user: USER,
			pass: PASSWORD
		},
		// use up to 5 parallel connections
		maxConnections: 5,
		// do not send more than 10 messages per connection
		maxMessages: 99,
		// do not send more than 5 messages in a second
		rateLimit: 50
	}));
	var mailOptions = ({
		from: USER,
		to: to_,
		subject: "[Occurences: " + occur +"] " + subj,
		//text: 'test', // disabled html format for emailer
		html: msg
	});

	transporter.sendMail(mailOptions, function(error, info){
		if(error) console.log(error);
		else console.log('Message sent: ' + info.response);
	});
	
	transporter.close();
}

/* clears temporary database every after script starts */
function initialize()
{
	MongoClient.connect(checker, function(err, db)
	{
		var col = db.collection("emails");
		col.remove();
		col.dropIndexes();
		col.createIndex({sentAt : 1}, {expireAfterSecoonds : EXPIRE_SECONDS}); //10 000
	});
}

function filter(doc, occur)
{
	MongoClient.connect(checker, function(err, db)
	{
		if(err) console.log(err);
		var col = db.collection(checker_collection);
		
		// check temp db if error exists
		col.findOne({ErrorMessage : doc.ErrorMessage},
		function(err, res)
		{
			if(res == null) // new error
			{
				console.log("#send email " + doc.EmailSubject);
				col.insert(
				{
					"sentAt" : new Date(),
					"Timestamp" : Math.floor(new Date/1000),
					"ErrorMessage" : doc.ErrorMessage,
					"Occurences" : occur
				});
				userPref(doc, occur);
			}
			else // updates old errors
			{
				col.findOne({ErrorMessage : doc.ErrorMessage},
				function (err, d)
				{
					var curr = Math.floor(new Date()/1000);
					var prev = d.Timestamp;
					if((curr - prev) < ERROR_LIFESPAN) // update old error only if less than defined error lifespan (in temp db)
					{
						console.log("update");
						col.update
						(
							{ErrorMessage : doc.ErrorMessage},
							{$set : {Occurences : (occur + 1)}}
						);
					}
					else
					{
						console.log("ERROR ALREADY EXISTS 10 MINS UP");
						userPref(doc, occur);
						col.deleteOne({ErrorMessage : doc.ErrorMessage});
						col.insert(
						{
							"sentAt" : new Date(),
							"Timestamp" : Math.floor(new Date/1000),
							"ErrorMessage" : doc.ErrorMessage,
							"Occurences" : 1 // reset occurence to 1 right after old error has expired
						});
					}
				});
			}
		}
		);
	});
}

function test(col, res, i, occur)
{
	col.find({ErrorMessage : res[i]._id}).sort({_id:-1}).limit(1).toArray(
	function(err, doc)
	{
		filter(doc[0], occur);
	});
}

initialize();
MongoClient.connect(main, function(err, db)
{
	if(err) console.log(err);
	
	var col = db.collection(main_collection);
	function poll()
	{
		var start = Math.floor(new Date()/1000) - POLL_RANGE;
		var next = start + POLL_RANGE;
		/* error polling */
		col.aggregate
		(
			{ $match : {Timestamp :  {$gte: start, $lt : next}}},
			{ $group:{ _id: '$ErrorMessage', Occurences: { $sum: 1 }}
		},
		function (err, res)
		{			
			for(var i = 0; i < res.length; i++)
			{
				var occur = res[i].Occurences;
				/* gets latest unique error logged for every time frame */
				test(col, res, i, occur);
			}
		}
		);
		console.log("-------------------");
	}

	setInterval(poll, POLL_INTERVAL);
});