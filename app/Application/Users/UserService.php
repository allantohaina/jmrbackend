<?php

namespace App\Application\Users;

use App\Application\Shared\Result;
use App\History\AdminHistory;
use App\History\TokenHistory;
use App\History\UserHistory;
use App\Libraries\JWTLibrary;
use App\Models\BanModel;
use App\Models\BlacklistModel;
use App\Models\IpBlocklistModel;
use App\Models\RefreshTokenModel;
use App\Models\TokenBlacklistModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;
use EmailValidator\EmailValidator;

class UserService
{
    private function verifyEmail(string $email): Result
    {
        // Local disposable domain check (offline, no API dependency)
        $disposableDomains = [
            '10minutemail.com', '10minutemail.net', '10minutemail.org',
            'guerrillamail.com', 'guerrillamail.org', 'guerrillamail.net',
            'mailinator.com', 'mailinator.org', 'mailinator.net',
            'tempmail.com', 'tempmail.net', 'tempmail.org',
            'throwaway.email', 'throwaway.mail',
            'yopmail.com', 'yopmail.net', 'yopmail.fr',
            'trashmail.com', 'trashmail.net', 'trashmail.org',
            'sharklasers.com', 'grr.la', 'guerrillamail.biz',
            'mailnator.com', 'discard.email', 'discardmail.com',
            'spambox.us', 'spambox.info', 'mailexpire.com',
            'maildrop.cc', 'getairmail.com', 'getnada.com',
            'temp-mail.org', 'temp-mail.ru', 'emailfake.com',
            'fakeinbox.com', 'fake-mail.net', 'mailcatch.com',
            'mintemail.com', 'mytrashmail.com', 'spamgourmet.com',
            'spamhere.com', 'spamspot.com', 'wegwerfmail.de',
            'wegwerfmail.net', 'wegwerfmail.org', 'jetable.org',
            'jetable.com', 'jetable.net', 'mxfuel.com',
            'mxtoolbox.com', 'sneakemail.com', 'sogetthis.com',
            'thankyou2010.com', 'thankyou2011.com', 'trash2009.com',
            'trashdevil.com', 'trashymail.com', 'tyldd.com',
            'uggsrock.com', 'weg-werf-mail.de', 'wh4f.org',
            'whyspam.me', 'willselfdestruct.com', 'winemaven.info',
            'wronghead.com', 'wuzup.net', 'xagloo.com',
            'xemaps.com', 'xents.com', 'xmaily.com', 'xoxy.net',
            'yep.it', 'yogamaven.com', 'yopmail.fr', 'ypmail.webarnak.fr.eu.org',
            'yuurok.com', 'zehnminutenmail.de', 'zippymail.info',
            'zoaxe.com', 'zoemail.org', '24hourmail.com',
            '2prong.com', '4warding.com', '4warding.net',
            'abyssmail.com', 'afrobacon.com', 'agtx.net',
            'alivance.com', 'anjing.cf', 'anjing.gq', 'antichef.com',
            'antispam24.de', 'armyspy.com', 'avastczech.com',
            'baxomale.ht.cx', 'beddly.com', 'binkmail.com',
            'bio-muesli.net', 'bobmail.info', 'bodhi.lawlita.com',
            'bofthew.com', 'bootybay.de', 'boun.cr', 'bouncyhouse.net',
            'boxformail.com', 'breakthesec.com', 'brefmail.com',
            'brennendesreich.de', 'broadbandninja.com', 'bsnow.net',
            'buffemail.com', 'bulkbye.com', 'bund.us', 'buymoreplays.com',
            'byby.me', 'c2.hu', 'c4an.com', 'cachedot.net',
            'car101.pro', 'cartelera.org', 'cd.mintemail.com',
            'ce.mintemail.com', 'cf.mintemail.com', 'clay.xyz',
            'cliptik.net', 'clrmail.com', 'cmail.com', 'cnamed.com',
            'co.mintemail.com', 'cobarekyo.ml', 'coldemail.info',
            'compareshippingrates.org', 'cool.fr.nf', 'courriel.fr.nf',
            'courrieltemporaire.com', 'crapmail.org', 'crazymailing.com',
            'curryworld.de', 'cust.in', 'dacoolest.com', 'dandikmail.com',
            'dayrep.com', 'deadaddress.com', 'deadspam.com',
            'delikkt.de', 'derkombi.de', 'despam.it', 'devnullmail.com',
            'digitalsanctuary.com', 'dingbone.com', 'discard.email',
            'discardmail.com', 'disposable.in', 'disposableaddress.com',
            'disposableemail.net', 'disposableemailaddresses.com',
            'disposabloid.com', 'dispostable.com', 'dm.wiki.unicorn.xyz',
            'dodgeit.com', 'dodgit.com', 'dodgit.org', 'doieaj.com',
            'domeny.sklep.pl', 'dontreg.com', 'drdrb.com', 'drdrb.net',
            'dropboxmail.com', 'dump-email.info', 'dumpandjunk.com',
            'dumpmail.com', 'dumpmail.de', 'dumpyemail.com', 'e4ward.com',
            'easytrashmail.com', 'einmalmail.de', 'einrot.com',
            'eintagsmail.de', 'email-fake.com', 'email-go.com',
            'email-jetable.fr', 'email-temporaire.fr', 'emailabc.com',
            'emaildom.xyz', 'emailfake.com', 'emailias.com',
            'emailinfive.com', 'emailisvalid.com', 'emaillime.com',
            'emailmiser.com', 'emailo.at', 'emails.ga', 'emailsecur.com',
            'emailsecur.net', 'emailspam.com', 'emailtemporanee.com',
            'emailtemporario.com.br', 'emailthe.net', 'emailtmp.com',
            'emailto.de', 'emailwarden.com', 'emailx.at', 'emailx.net',
            'emailxfer.com', 'emz.net', 'enterto.com', 'ephemail.net',
            'etranquil.com', 'etranquil.net', 'etranquil.org',
            'evopo.com', 'example.com', 'fakemail.com', 'fakemail.net',
            'fakemailgenerator.com', 'fammix.com', 'fansworldwide.de',
            'fantasymail.de', 'fastacura.com', 'fastchevy.com',
            'fastchrysler.com', 'fastkawasaki.com', 'fastmazda.com',
            'fastmitsubishi.com', 'fastnissan.com', 'fastsubaru.com',
            'fastsuzuki.com', 'fasttoyota.com', 'fastyamaha.com',
            'fatflap.com', 'fdfdsfds.com', 'fightallspam.com',
            'filzmail.com', 'fixmail.tk', 'fivemail.de', 'fixmail.tk',
            'fizmail.com', 'flashbox.5july.org', 'fleckens.hu',
            'flurred.com', 'flyspam.com', 'footard.com', 'forgetmail.com',
            'fourwarding.com', 'free-email.ga', 'freemaillink.com',
            'freemailnow.net', 'freeplumper.com', 'fufmeatloaf.com',
            'funkymail.de', 'fux0rduck.org', 'fxnxs.com', 'garbagemail.net',
            'garliclife.com', 'gelitik.in', 'get1mail.com', 'get2mail.com',
            'getairmail.com', 'getmail.us', 'getnada.com', 'ghosttexter.de',
            'gibraltarbyfrank.com', 'girlnc.com', 'giybfu.com',
            'go.irc.so', 'goemailgo.com', 'gomail.in', 'great-host.in',
            'greensloth.com', 'gsrv.co.uk', 'guerrillamail.net',
            'gustr.com', 'h.mintemail.com', 'h8s.org', 'haltospam.com',
            'harakirimail.com', 'hartbot.de', 'hentai-email.com',
            'hidemail.de', 'hidemyass.com', 'hiddencorner.xyz',
            'hiddentrak.com', 'hideserv.com', 'hmail.us', 'hobbiesare.com',
            'hopemail.biz', 'hotpop.com', 'hstermail.com', 'hulapla.de',
            'ibnuh.bz', 'icx.in', 'ignoremail.com', 'imails.info',
            'inbaca.com', 'inboxbear.com', 'inboxclean.com',
            'inboxclean.org', 'inboxproxy.com', 'incognitomail.com',
            'incognitomail.net', 'incognitomail.org', 'indirect.ws',
            'infocom.zp.ua', 'instant-mail.de', 'instantemailaddress.com',
            'ip6.li', 'ipoo.org', 'irish2me.com', 'iwi.net',
            'j.9q.ro', 'j.s.ly', 'jet-renovation.fr', 'jetable.com',
            'jetable.fr.nf', 'jetable.net', 'jetable.org', 'jobbikszextural.hu',
            'jopho.com', 'jungkamushukum.com', 'kademen.com',
            'kasmail.com', 'kaspop.com', 'killmail.com', 'killmail.net',
            'kimsdisk.com', 'kingsq.ga', 'kir.ch.tc', 'klassmaster.com',
            'klassmaster.net', 'klenot.sk', 'klzlk.com', 'koptermail.com',
            'kulturbetrieb.info', 'kuvatamo.fi', 'l33r.eu', 'la.at',
            'labworld.org', 'lakelivingstonrealestate.com', 'lal.0fees.net',
            'landmail.co', 'lastmail.co', 'lastmail.com', 'lavabit.com',
            'lazyinbox.us', 'legitmail.club', 'lembaranbaik.cf',
            'lembaranbaik.gq', 'letmeinonthis.com', 'letthemeatspam.com',
            'lins.kr', 'live-share.com', 'lopl.co.cc', 'loud-music.net',
            'loves.dickshare.net', 'lr78.com', 'lroid.com', 'lukop.dk',
            'maboard.com', 'macromaid.com', 'magamail.com', 'mail-t.tk',
            'mail.by', 'mail.mezimages.net', 'mail.piaa.me',
            'mail.rambler.ru', 'mail0.cf', 'mail1.drama.tw',
            'mail2.maillvna.com', 'mail22.club', 'mail333.com',
            'mail4trash.com', 'mailbiz.biz', 'mailbucket.org',
            'mailcat.biz', 'mailcatch.com', 'mailde.de', 'mailde.info',
            'maildrop.cc', 'maildrop.me', 'maildu.de', 'maildx.com',
            'maileater.com', 'mailexpire.com', 'mailfa.tk',
            'mailforspam.com', 'mailfree.ga', 'mailfree.gq',
            'mailfree.ml', 'mailhaven.com', 'mailhex.com',
            'mailhood.com', 'mailimate.com', 'mailin8r.com',
            'mailinatar.com', 'mailinater.com', 'mailinator.com',
            'mailinator.gq', 'mailinator.net', 'mailinator.org',
            'mailinator2.com', 'mailinbox.cf', 'mailincubator.com',
            'mailismagic.com', 'mailjunk.org', 'mailmate.com',
            'mailme.xyz', 'mailmetrash.com', 'mailmoat.com',
            'mailmoth.com', 'mailms.com', 'mailn.de', 'mailnator.com',
            'mailna.win', 'mailnow2.com', 'mailnuo.com', 'mailnull.com',
            'mailops.com', 'mailorc.com', 'mailorg.org', 'mailpick.biz',
            'mailpooch.com', 'mailpride.com', 'mailproxsy.com',
            'mailquack.com', 'mailrocket.biz', 'mailsac.com',
            'mailscrap.com', 'mailseal.de', 'mailshell.com',
            'mailshiv.com', 'mailsiphon.com', 'mailslapping.com',
            'mailslite.com', 'mailsuck.org', 'mailtemple.net',
            'maintemporaire.com', 'mailtest.buzz', 'mailtoy.net',
            'mailtv.net', 'mailtv.tv', 'mailzilla.com', 'mailzilla.org',
            'malakasa.ml', 'mansiondev.com', 'manybrain.com',
            'mbx.cc', 'mciek.com', 'mega.zik.dj', 'meinspamschutz.de',
            'merda.cf', 'merda.gq', 'merda.ml', 'messagebeamer.de',
            'mezimages.net', 'mfsa.info', 'mierdamail.com', 'migmail.net',
            'migmail.pl', 'migumail.com', 'mintemail.com',
            'mjukglass.nu', 'moakt.com', 'mobi.web.id', 'mobileninja.co.uk',
            'moncourrier.fr.nf', 'monemail.fr.nf', 'monmail.fr.nf',
            'monumentmail.com', 'mor19.uu.gl', 'mountainregionallibrary.net',
            'mt2009.com', 'mt2014.com', 'mt2015.com', 'mua.gl',
            'muathegame.com', 'muc.space', 'mycleaninbox.net',
            'myemail.xyz', 'mygeoweb.info', 'myindohome.services',
            'myinterserver.ml', 'mykickass.net', 'mymail-in.net',
            'mymail90.com', 'mymailoasis.com', 'mynetaccount.com',
            'mypartyclip.de', 'myphantomemail.com', 'mysamp.de',
            'myspaceinc.com', 'myspaceinc.net', 'mytemp.email',
            'mytempemail.com', 'mytruemail.co.za', 'mywarnernet.net',
            'n.ml', 'n0t.one', 'n8.gs', 'negated.com', 'neomailbox.com',
            'nepwk.com', 'nervmich.net', 'netmails.com', 'netmails.net',
            'netricity.nl', 'netris.net', 'neverbox.com', 'next.ovh',
            'nextstopvalhalla.com', 'nice-4u.com', 'nincsmail.hu',
            'nmail.cf', 'nnot.net', 'no-spam.ws', 'noblep.M.ocn.ne.jp',
            'nobulk.com', 'noclickemail.com', 'nogmailspam.info',
            'nomail.cf', 'nomail.ch', 'nomail.ga', 'nomail.gq',
            'nomail.ml', 'nomail.xl.cx', 'nomorespamemails.com',
            'nonspam.eu', 'nonspammer.de', 'noref.in', 'nospam.win',
            'nospam4.us', 'nospamfor.us', 'nothanks.com', 'nothingtoseehere.ca',
            'notmail.com', 'notmail.net', 'notrnailinator.com',
            'nowhere.org', 'nowmymail.com', 'ntlhelp.net', 'nyrmusic.com',
            'o.cowboymail.net', 'o.o.g.', 'o.opendns.ro', 'obfusko.com',
            'odnorazovoe.ru', 'oepia.com', 'ohi.tw', 'oida.icu',
            'olypmall.ru', 'one-time.email', 'oneoffemail.com',
            'oneoffmail.com', 'onewaymail.com', 'onlatedotcom.info',
            'online.ms', 'opayq.com', 'ordinaryamerican.net',
            'otherinbox.com', 'ourawesome.life', 'outlawspam.com',
            'ovpn.to', 'owlpic.net', 'packersu.ga', 'pancakemail.com',
            'paplease.com', 'pcusers.otherinbox.com', 'pepbot.com',
            'petrolstation.hu', 'pfui.ru', 'photomark.net', 'pi.vu',
            'pig.pp.ua', 'piiym.com', 'pinoymail.ml', 'pjjkp.com',
            'plexolan.de', 'poczta.onet.pl', 'pokemail.net',
            'politikerclub.de', 'poofy.org', 'pookmail.com', 'privacy.net',
            'privatdemail.net', 'privy-mail.com', 'privymail.de',
            'projectcl.com', 'proxymail.eu', 'prtnx.com', 'prtz.eu',
            'punkass.com', 'putthisinyourspamdatabase.com', 'pw.epac.to',
            'q.buzz', 'qipmail.net', 'qisdo.com', 'qisoa.com',
            'quickinbox.com', 'quickmail.nl', 'rcpt.at', 'reality-concept.club',
            'reallymymail.com', 'recyclemail.dk', 'regbypass.com',
            'regbypass.com', 'rejectmail.com', 'remail.cf', 'remail.ga',
            'rhyta.com', 'ricret.com', 'rkomo.com', 'roadkill.free.fr.nf',
            'rocketmail.Com', 'ro.lt', 'rollindo.agency', 'rootfest.net',
            'royal.net', 'rppkn.com', 'rq1.in', 'rr-1.com',
            'rr-2.com', 'rr-3.com', 'rtrtr.com', 's.0belix.net',
            's.1sweep.com', 's.s.m.', 's.s.notify.0am.jp',
            's01.xyz', 's2.st.ne.jp', 's3.st.ne.jp', 'safetymail.info',
            'safetypost.de', 'sandelf.de', 'saynotospams.com',
            'scartmail.com', 'schafmail.de', 'schrott-email.de',
            'scotsh.com', 'secretemail.de', 'secure-box.info',
            'selfdestructingmail.com', 'selfdestructingmail.org',
            'sendspamhere.com', 'senseless-entertainment.com',
            'server.ms', 'services951.com', 'sh.gg', 'shararmail.ga',
            'sharklasers.com', 'shieldedmail.com', 'shiftrpg.com',
            'shmeriously.com', 'shortmail.net', 'sibmail.com',
            'sinnlos-mail.de', 'siteposter.com', 'slapsmail.ml',
            'slaskpost.se', 'slave-auctions.net', 'slippery.email',
            'slopsbox.com', 'slushmail.com', 'smapfree24.com',
            'smapfree24.de', 'smapfree24.eu', 'smapfree24.info',
            'smapfree24.org', 'smashmail.de', 'smellfear.com',
            'smellrear.com', 'sneakemail.com', 'sneakmail.de',
            'socialfurry.org', 'sofimail.com', 'sofort-mail.de',
            'softpls.asia', 'sogetthis.com', 'sohu.com', 'solvemail.info',
            'spam-be-gone.com', 'spam.care', 'spam.coroiu.com',
            'spam.de', 'spam.deluser.net', 'spam.dh-n.net',
            'spam.h3q.com', 'spam.h2.tc', 'spam.io', 'spam.la',
            'spam.lvl4.org', 'spam.net', 'spam.org', 'spam.ozh.org',
            'spam.su', 'spam.trajano.net', 'spam.wincology.net',
            'spam.x52.de', 'spam.zzz.com', 'spam4.me',
            'spamail.de', 'spamarrest.com', 'spamavert.com',
            'spambob.com', 'spambob.net', 'spambob.org', 'spambog.com',
            'spambog.de', 'spambog.net', 'spambog.ru', 'spambox.info',
            'spambox.us', 'spamcannon.com', 'spamcannon.net',
            'spamcero.com', 'spamcon.org', 'spamcorptastic.com',
            'spamcowboy.com', 'spamcowboy.net', 'spamcowboy.org',
            'spamday.com', 'spamdecoy.net', 'spamex.com',
            'spamfighter.cf', 'spamfighter.ga', 'spamfighter.gq',
            'spamfighter.ml', 'spamfree24.com', 'spamfree24.de',
            'spamfree24.eu', 'spamfree24.info', 'spamfree24.net',
            'spamfree24.org', 'spamgoes.in', 'spamgourmet.com',
            'spamgourmet.net', 'spamgourmet.org', 'spamhere.com',
            'spamherelots.com', 'spamhereplease.com', 'spamhole.com',
            'spamify.com', 'spaminator.de', 'spamkill.info',
            'spaml.com', 'spamlot.net', 'spammotel.com', 'spamobox.com',
            'spamoff.de', 'spamsalad.in', 'spamserver.info',
            'spamserver.net', 'spamserver.org', 'spamslicer.com',
            'spamsphere.com', 'spamspot.com', 'spamstack.net',
            'spamthis.co.uk', 'spamthis.network', 'spamtrail.com',
            'spamtrap.ro', 'spamwc.de', 'speed.1s.fr', 'spidernet.com.ua',
            'spoofmail.de', 'squizzy.de', 'ssoia.com', 'startag.org',
            'starlight-breaker.net', 'startfu.com', 'stealthmail.com',
            'stop.myspam.org', 'storemail.de', 'stumpfwerk.com',
            'suburbanthug.com', 'suckmyd.com', 'surrenderat20.com',
            'svxr.org', 'sweetlocal.com', 'tafmail.com', 'taglead.com',
            'talkmises.com', 'talknarnia.com', 'talkvisions.com',
            'tapchicuoihoi.com', 'teemr.com', 'teleworm.com',
            'teleworm.us', 'temp-emails.com', 'temp-mail.com',
            'temp-mail.de', 'temp-mail.net', 'temp-mail.org',
            'temp-mail.ru', 'temp.em', 'temp.headstrong.de',
            'tempail.com', 'tempalias.com', 'tempe-mail.com',
            'tempemail.co', 'tempemail.com', 'tempemail.net',
            'tempemail.org', 'tempinbox.com', 'tempmail.co',
            'tempmail.de', 'tempmail.eu', 'tempmail.it', 'tempmail.net',
            'tempmail.org', 'tempmail.us', 'tempmail.win',
            'tempmail.xyz', 'tempmailer.com', 'tempmailo.com',
            'tempomail.fr', 'temporarily.de', 'temporarioemail.com.br',
            'temporary-email.com', 'temporary.email', 'temporaryforwarding.com',
            'temporaryinbox.com', 'temporarymail.org', 'tempthe.net',
            'tempymail.com', 'thankyou2010.com', 'thisisnotmyrealemail.com',
            'thismail.net', 'throwamail.com', 'throwaway.email',
            'throwaway.mail', 'throwaway.xyz', 'throwawayemail.com',
            'throwawaymail.com', 'tilien.com', 'tittbit.in',
            'tizi.com', 'tkitc.de', 'toiea.com', 'toomail.biz',
            'topranklist.de', 'top-shop-tovar.ru', 'totoan.info',
            'tradermail.info', 'trash-amil.com', 'trash-mail.com',
            'trash-mail.de', 'trash2009.com', 'trash2010.com',
            'trash2011.com', 'trashdevil.com', 'trashdevil.de',
            'trashemail.de', 'trashinbox.net', 'trashmail.at',
            'trashmail.com', 'trashmail.de', 'trashmail.ga',
            'trashmail.gq', 'trashmail.me', 'trashmail.net',
            'trashmail.org', 'trashmail.ws', 'trashmailer.com',
            'trashmails.com', 'trashymail.com', 'trbvm.com',
            'trbvn.com', 'trialmail.de', 'trillianpro.com',
            'tryalert.com', 'turual.com', 'tuyulmoklet.cf',
            'tuyulmoklet.ga', 'tuyulmoklet.gq', 'tuyulmoklet.ml',
            'tvchd.com', 'tverya.com', 'twkly.ml', 'two.pw',
            'tyldd.com', 'uggsrock.com', 'umail.net', 'unit7lahaina.com',
            'upliftnow.com', 'uplipht.com', 'uploadnolimit.com',
            'urfunktion.se', 'uroid.com', 'us.af', 'us.to',
            'ushijima1128.ml', 'utc.lv', 'v.0v.ro', 'valemail.net',
            'venompen.com', 'verifymywhois.com', 'veryrealemail.com',
            'vidchart.com', 'viralhits.org', 'vjtim.hotmail.com',
            'vjtim.hotmail.com', 'vmail.me', 'vmailing.info',
            'vmani.com', 'vnedu.me', 'voidbay.com', 'vorga.org',
            'votiputox.org', 'voxelcore.com', 'vpn.st',
            'vps30.com', 'vps911.net', 'vwmail.gq', 'w3internet.co.uk',
            'wakingupesther.com', 'walala.org', 'walkmail.net',
            'walkmail.ru', 'wasntmyfault.com', 'wazmua.cf', 'web2mailco.com',
            'webemail.me', 'webm4il.info', 'webuser.in', 'wee.my',
            'weg-werf-mail.de', 'wegwerfemail.de', 'wegwerfmail.de',
            'wegwerfmail.net', 'wegwerfmail.org', 'wh4f.org',
            'whyspam.me', 'wibblesmith.com', 'wicked.cf',
            'wicked.gq', 'wicked.ml', 'widaryanto.info', 'willselfdestruct.com',
            'winemaven.info', 'wins.com.br', 'wmail.cf', 'wmail.ga',
            'wmail.gq', 'wmail.ml', 'wollan.info', 'worldbreak.com',
            'wowmail.com', 'wronghead.com', 'wuzup.net', 'xagloo.com',
            'xemaps.com', 'xents.com', 'xgmailoo.com', 'xjoi.com',
            'xl.cx', 'xmail.com', 'xmaily.com', 'xn--9kq9673e.com',
            'xoxy.net', 'xpee.com', 'xrads.com', 'xwaretech.com',
            'xwaretech.info', 'xwaretech.net', 'xww.ro', 'yapped.net',
            'yeah.net', 'yep.it', 'yogamaven.com', 'yolooo.ml',
            'yoo.ro', 'yopmail.com', 'yopmail.fr', 'yopmail.gq',
            'yopmail.net', 'yopmail.org', 'yourtemplatemoney.com',
            'yroid.com', 'yuurok.com', 'z1p.biz', 'za.com',
            'zebins.com', 'zebins.eu', 'zehnminutenmail.de',
            'zenhacks.xyz', 'zeppe.xyz', 'zil4.com', 'zilmail.com',
            'zipo.cf', 'zipo.gq', 'ziporama.com', 'zippymail.info',
            'zoaxe.com', 'zoemail.com', 'zoemail.net', 'zoemail.org',
            'zombie-hive.com', 'zomg.info', 'zslsz.com',
            'zzz.com', 'zzz.pl',
        ];
        $domain = strtolower(substr(strrchr($email, '@'), 1));
        if (in_array($domain, $disposableDomains)) {
            return Result::fail([
                'error' => 'Les adresses email jetables ne sont pas autorisées.',
            ], 400);
        }

        try {
            $emailValidator = new EmailValidator([
                'checkMxRecords' => true,
                'checkDisposableEmail' => true,
            ]);

            $isValid = $emailValidator->validate($email);

            if (!$isValid) {
                $errorReason = $emailValidator->getErrorReason();
                return Result::fail([
                    'error' => 'L\'adresse e-mail n\'est pas valide.',
                    'reason' => $errorReason,
                ], 400);
            }

            return Result::ok();
        } catch (\Exception $e) {
            log_message('error', 'Email validation failed: ' . $e->getMessage());
            // If validation API fails, rely on local disposable check above
            return Result::ok();
        }
    }

    public function register(array $input, RequestInterface $request): Result
    {
        $model = new UserModel();

        $email = $input['email'] ?? null;

        $data = [
            'email' => $email,
            'password' => $input['password'] ?? null,
            'first_name' => $input['first_name'] ?? null,
            'last_name' => $input['last_name'] ?? null,
            'phone' => $input['phone'] ?? null,
            'birth_date' => $input['birth_date'] ?? null,
            'country' => $input['country'] ?? null,
            'address' => $input['address'] ?? null,
            'role' => 'user',
        ];

        $missing = $this->validateRequired($data, ['email', 'password', 'first_name', 'last_name']);
        if (!empty($missing)) {
            return Result::fail([
                'error' => lang('Users.errors.required_fields'),
                'missing' => $missing,
            ], 400);
        }

        // Verify email with EmailValidator
        $emailVerification = $this->verifyEmail($email);
        if (!$emailVerification->isSuccess()) {
            return $emailVerification;
        }

        // Check blacklist
        $ip = $request->getIPAddress();
        if ((new BlacklistModel())->isBlacklisted($data['email'], $ip)) {
            return Result::fail('Inscription non autorisée.', 403);
        }

        // Sanitize email to avoid duplicates (remove Gmail plus trick)
        try {
            $emailValidator = new EmailValidator();
            $emailValidator->validate($email);
            if ($emailValidator->isGmailWithPlusChar()) {
                $data['email'] = $emailValidator->getGmailAddressWithoutPlus();
            }
        } catch (\Exception $e) {
            log_message('error', 'Failed to sanitize email: ' . $e->getMessage());
        }

        if (!$model->insert($data)) {
            return Result::fail($model->errors(), 400);
        }

        $userId = $model->getInsertID();
        $user = $model->getUserById($userId);

        $tokens = $this->issueTokens($user, $request);

        (new UserHistory())->logRegister($request, $user);

        return Result::created([
            'message' => lang('Users.register.success'),
            'user' => $user,
            'token' => $tokens['token'],
            'refresh_token' => $tokens['refresh_token'],
        ]);
    }

    public function login(array $input, RequestInterface $request): Result
    {
        $model = new UserModel();

        $email = $input['email'] ?? null;
        $password = $input['password'] ?? null;

        if (!$email || !$password) {
            return Result::fail(lang('Users.login.required'), 400);
        }

        $ip = $request->getIPAddress();
        $ipBlocklistModel = new IpBlocklistModel();

        if ($ipBlocklistModel->isBlocked($ip)) {
            return Result::fail('Trop de tentatives. Réessayez dans quelques minutes.', 429);
        }

        $maxAttempts = (int) (getenv('LOGIN_MAX_ATTEMPTS') ?: 5);
        $lockMinutes = (int) (getenv('LOGIN_LOCK_MINUTES') ?: 15);

        $userRecord = $model->getUserForLogin($email);

        if (!$userRecord || !($userRecord['is_active'] ?? false)) {
            $ipBlocklistModel->incrementFailedAttempts($ip, 10);
            $ban = $userRecord ? (new BanModel())->getActiveBan($userRecord['id']) : null;
            (new UserHistory())->logLoginFailed($request, $email, $userRecord['id'] ?? null, $ban ? 'banned' : 'invalid_credentials');
            if ($ban) {
                return Result::fail(['error' => 'Votre compte a été suspendu. Motif : ' . $ban['reason']], 403);
            }
            return Result::fail(lang('Users.login.invalid'), 401);
        }

        if (!empty($userRecord['locked_until']) && strtotime($userRecord['locked_until']) > time()) {
            $ipBlocklistModel->incrementFailedAttempts($ip, 10);
            (new UserHistory())->logLoginFailed($request, $email, $userRecord['id'] ?? null, 'locked');
            return Result::fail(lang('Users.login.locked'), 423);
        }

        if (!password_verify($password, $userRecord['password_hash'])) {
            $ipBlocklistModel->incrementFailedAttempts($ip, 10);
            $failed = ((int) ($userRecord['failed_login_count'] ?? 0)) + 1;
            $lockedUntil = null;
            if ($failed >= $maxAttempts) {
                $lockedUntil = date('Y-m-d H:i:s', time() + ($lockMinutes * 60));
            }
            $model->recordLoginFailure($userRecord['id'], $failed, $lockedUntil);
            (new UserHistory())->logLoginFailed(
                $request,
                $email,
                $userRecord['id'] ?? null,
                $lockedUntil ? 'locked' : 'invalid_password'
            );

            if ($lockedUntil) {
                return Result::fail(lang('Users.login.locked'), 423);
            }

            return Result::fail(lang('Users.login.invalid'), 401);
        }

        $ipBlocklistModel->clearFailedAttempts($ip);
        $model->recordLoginSuccess($userRecord['id']);
        $user = $model->getUserById($userRecord['id']);

        $tokens = $this->issueTokens($user, $request);

        (new UserHistory())->logLogin($request, $user);

        return Result::ok([
            'message' => lang('Users.login.success'),
            'user' => $user,
            'token' => $tokens['token'],
            'refresh_token' => $tokens['refresh_token'],
        ]);
    }

    public function profile(?string $userId): Result
    {
        return $this->findUserById($userId);
    }

    public function updateProfile(?string $userId, array $input, RequestInterface $request): Result
    {
        return $this->updateUserRecord((string) $userId, $input, $request, false);
    }

    public function deleteProfile(?string $userId, RequestInterface $request): Result
    {
        return $this->deleteUserRecord(
            (string) $userId,
            static fn($before) => (new UserHistory())->logProfileDelete($request, (string) $userId, $before),
            'Users.errors.delete_account',
            'Users.profile.deleted'
        );
    }

    public function listUsers(): Result
    {
        $model = new UserModel();
        return Result::ok($model->getAllUsers());
    }

    public function getUser(?string $id): Result
    {
        return $this->findUserById($id);
    }

    public function updateUser(?string $id, array $input, ?string $actorId, RequestInterface $request): Result
    {
        return $this->updateUserRecord((string) $id, $input, $request, true, $actorId);
    }

    public function deleteUser(?string $id, ?string $actorId, RequestInterface $request): Result
    {
        return $this->deleteUserRecord(
            (string) $id,
            static fn($before) => (new AdminHistory())->logUserDelete($request, $actorId, (string) $id, $before),
            'Users.errors.delete_user',
            'Users.admin.deleted'
        );
    }

    public function refreshToken(?string $refreshToken, RequestInterface $request): Result
    {
        if (!$refreshToken) {
            return Result::fail(lang('Users.refresh.required'), 400);
        }

        $model = new RefreshTokenModel();
        $hash = hash('sha256', $refreshToken);
        $record = $model->where('token_hash', $hash)->first();

        if (!$record || $record['revoked_at'] !== null) {
            return Result::fail(lang('Users.refresh.invalid'), 401);
        }

        if (strtotime($record['expires_at']) < time()) {
            return Result::fail(lang('Users.refresh.expired'), 401);
        }

        $userModel = new UserModel();
        $user = $userModel->getUserById($record['user_id']);
        if (!$user) {
            return Result::notFound(lang('Users.errors.not_found'));
        }

        $tokens = $this->issueTokens($user, $request);
        $token = $tokens['token'];
        $newRefresh = $tokens['refresh_token'];
        $newRefreshId = $this->getRefreshTokenId($newRefresh);

        $model->update($record['id'], [
            'revoked_at' => date('Y-m-d H:i:s'),
            'replaced_by' => $newRefreshId,
        ]);

        $jwt = new JWTLibrary();
        $decoded = $jwt->decode($token);
        $jti = $decoded->jti ?? null;
        (new TokenHistory())->log(
            $request,
            'refresh',
            $user['id'],
            $jti,
            $newRefreshId,
            ['refresh_token_rotated' => true]
        );

        return Result::ok([
            'token' => $token,
            'refresh_token' => $newRefresh,
        ]);
    }

    public function logout(?string $refreshToken, ?string $authHeader, RequestInterface $request): Result
    {
        if ($refreshToken) {
            $model = new RefreshTokenModel();
            $hash = hash('sha256', $refreshToken);
            $record = $model->where('token_hash', $hash)->first();
            if ($record && $record['revoked_at'] === null) {
                $model->update($record['id'], [
                    'revoked_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
            $jwt = new JWTLibrary();
            $decoded = $jwt->decode($token);
            if ($decoded && isset($decoded->jti, $decoded->exp)) {
                $blacklist = new TokenBlacklistModel();
                $blacklist->insert([
                    'id' => $this->uuidV4(),
                    'jti' => $decoded->jti,
                    'expires_at' => date('Y-m-d H:i:s', (int) $decoded->exp),
                    'revoked_at' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'reason' => 'logout',
                ]);
                (new TokenHistory())->log(
                    $request,
                    'logout',
                    $decoded->user_id ?? null,
                    $decoded->jti,
                    $refreshToken ? $this->getRefreshTokenId($refreshToken) : null,
                    null
                );
            }
        }

        return Result::ok([
            'message' => lang('Users.logout.success'),
        ]);
    }

    private function validateRequired(array $data, array $fields): array
    {
        $missing = [];
        foreach ($fields as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '') {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    private function findUserById(?string $id): Result
    {
        $model = new UserModel();
        $user = $model->getUserById((string) $id);

        if (!$user) {
            return Result::notFound(lang('Users.errors.not_found'));
        }

        return Result::ok($user);
    }

    private function updateUserRecord(
        string $id,
        array $input,
        RequestInterface $request,
        bool $isAdminUpdate,
        ?string $actorId = null
    ): Result {
        $model = new UserModel();
        $before = $model->getUserById($id);
        $data = $this->prepareUpdateData($input, $isAdminUpdate);

        $missing = $this->validateRequired($data, ['email', 'first_name', 'last_name']);
        if (!empty($missing)) {
            return Result::fail([
                'error' => lang('Users.errors.required_fields'),
                'missing' => $missing,
            ], 400);
        }

        $rules = $model->getStrictUpdateRules();
        $rules['email'] = "required|valid_email|is_unique[users.email,id,{$id}]";
        $model->setValidationRules($rules);

        $updated = $isAdminUpdate ? $model->update($id, $data) : $model->updateProfile($id, $data);
        if (!$updated) {
            return Result::fail($model->errors(), 400);
        }

        $user = $model->getUserById($id);

        if ($isAdminUpdate) {
            (new AdminHistory())->logUserUpdate($request, $actorId, $id, $before, $user);

            return Result::ok([
                'message' => lang('Users.admin.updated'),
                'user' => $user,
            ]);
        }

        (new UserHistory())->logProfileUpdate($request, $id, $before, $user);

        return Result::ok([
            'message' => lang('Users.profile.updated'),
            'user' => $user,
        ]);
    }

    private function prepareUpdateData(array $input, bool $includeAdminFields): array
    {
        $data = [
            'first_name' => $input['first_name'] ?? null,
            'last_name' => $input['last_name'] ?? null,
            'phone' => $input['phone'] ?? null,
            'email' => $input['email'] ?? null,
        ];

        if ($includeAdminFields) {
            $data['role'] = $input['role'] ?? null;
            $data['is_active'] = $this->normalizeActiveValue($input['is_active'] ?? null);
        }

        if (array_key_exists('password', $input)) {
            $data['password'] = $input['password'];
        }

        return array_filter($data, static fn($value) => $value !== null);
    }

    private function normalizeActiveValue(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value)) {
            return $value ? '1' : '0';
        }

        return $value;
    }

    private function deleteUserRecord(
        string $id,
        callable $onDeleted,
        string $errorMessageKey,
        string $successMessageKey
    ): Result {
        $model = new UserModel();
        $before = $model->getUserById($id);

        if (!$model->delete($id)) {
            return Result::fail(lang($errorMessageKey), 500);
        }

        $onDeleted($before);

        return Result::ok([
            'message' => lang($successMessageKey),
        ]);
    }

    private function issueTokens(array $user, RequestInterface $request): array
    {
        $jwt = new JWTLibrary();

        return [
            'token' => $jwt->encode($this->buildTokenPayload($user)),
            'refresh_token' => $this->issueRefreshToken($user['id'], $request),
        ];
    }

    private function buildTokenPayload(array $user): array
    {
        return [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'scopes' => $this->getScopesForRole($user['role']),
        ];
    }

    private function issueRefreshToken(string $userId, RequestInterface $request): string
    {
        $model = new RefreshTokenModel();
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expiresAt = time() + (int) (getenv('JWT_REFRESH_TTL') ?: 60 * 60 * 24 * 30);
        $id = $this->uuidV4();

        // Handle CLIRequest which may not have all methods
        $ipAddress = method_exists($request, 'getIPAddress') ? $request->getIPAddress() : '127.0.0.1';
        $userAgent = method_exists($request, 'getUserAgent') ? substr((string) $request->getUserAgent(), 0, 255) : 'CLI';

        $model->insert([
            'id' => $id,
            'user_id' => $userId,
            'token_hash' => $hash,
            'expires_at' => date('Y-m-d H:i:s', $expiresAt),
            'revoked_at' => null,
            'replaced_by' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        return $token;
    }

    private function getRefreshTokenId(string $refreshToken): ?string
    {
        $model = new RefreshTokenModel();
        $hash = hash('sha256', $refreshToken);
        $record = $model->where('token_hash', $hash)->first();

        return $record['id'] ?? null;
    }

    private function getScopesForRole(string $role): array
    {
        if ($role === 'admin') {
            return ['users:read', 'users:write', 'admin:all'];
        }

        if ($role === 'worker') {
            return ['users:read', 'atelier:read', 'atelier:write'];
        }

        return ['users:read', 'users:write'];
    }

    private function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split($hex, 4));
    }
}

