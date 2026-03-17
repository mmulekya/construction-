import AsyncStorage from '@react-native-async-storage/async-storage';

export const saveChat = async (messages) => {
  try {
    await AsyncStorage.setItem('chat_history', JSON.stringify(messages));
  } catch (err) { console.log(err); }
};

export const loadChat = async () => {
  try {
    const data = await AsyncStorage.getItem('chat_history');
    return data ? JSON.parse(data) : [];
  } catch (err) { console.log(err); return []; }
};