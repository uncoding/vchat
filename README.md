## 微信个人订阅号功能的开发
<hr> 
#### 1、微信个人订阅号的自动回复功能，在申请注册帐号之后可以设定关键字进行回复，另外的也可以启用开发的功能进行自己开发；
####这里的自动回复主要功能是：在关注帐号之后发送文本消息自动的进行回复，回复的内容为歌词库中的一条歌词;数据是存储在新浪
####云平台的数据库中，实现方式，从对应的文本消息中随机的获取一条发送给客户;
#### 2、微信天气预报功能的开发，用户在关注的帐号中输入“XX天气”即可收到当前地方的天气状况，实现过程，调用已有的天气预报的接口，
####把当前的地方传送过去，找到对应的地区编码，然后进行查询，返回json格式的数据;
