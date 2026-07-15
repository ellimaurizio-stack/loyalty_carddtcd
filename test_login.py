import urllib.request
import urllib.parse
import ssl
import json

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

opener = urllib.request.build_opener(urllib.request.HTTPSHandler(context=ctx), urllib.request.HTTPCookieProcessor())
urllib.request.install_opener(opener)

req = urllib.request.Request('https://loyalty_carddtcd.on-forge.com/login')
response = urllib.request.urlopen(req)

csrf_token = ''
for cookie in opener.cookiejar:
    if cookie.name == 'XSRF-TOKEN':
        csrf_token = urllib.parse.unquote(cookie.value)

data = urllib.parse.urlencode({
    '_token': csrf_token,
    'email': 'admin@loyalty.com',
    'password': 'password'
}).encode('utf-8')

req_post = urllib.request.Request('https://loyalty_carddtcd.on-forge.com/login', data=data)
try:
    post_response = urllib.request.urlopen(req_post)
    print("Login Success! Status:", post_response.status)
    print("Final URL:", post_response.geturl())
except urllib.error.HTTPError as e:
    print("Login Failed! Status:", e.code)
    print(e.read().decode('utf-8')[:1000])
