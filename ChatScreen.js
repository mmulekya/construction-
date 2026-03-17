import React, {useState, useEffect} from 'react';
import {View, TextInput, Button, FlatList, Text, SafeAreaView} from 'react-native';
import api from '../api/apiClient';

export default function ChatScreen() {
  const [messages, setMessages] = useState([]);
  const [question, setQuestion] = useState('');

  const sendQuestion = async () => {
    if(!question) return;
    try {
      const res = await api.post('/ask_ai.php', {question, token: 'CSRF_TOKEN_HERE'});
      const answer = res.data.knowledge;
      setMessages(prev => [...prev, {question, answer}]);
      setQuestion('');
    } catch (err) {
      console.log('Error:', err);
    }
  };

  return (
    <SafeAreaView style={{flex:1, padding:10}}>
      <FlatList
        data={messages}
        keyExtractor={(item,i)=>i.toString()}
        renderItem={({item})=>(
          <View style={{marginBottom:10}}>
            <Text style={{fontWeight:'bold'}}>You: {item.question}</Text>
            <Text>AI: {item.answer}</Text>
          </View>
        )}
      />
      <TextInput
        placeholder="Ask BuildSmart..."
        value={question}
        onChangeText={setQuestion}
        style={{borderWidth:1, padding:8, marginBottom:5}}
      />
      <Button title="Send" onPress={sendQuestion}/>
    </SafeAreaView>
  );
}