import React, {useEffect, useState} from 'react';
import {View, Text, FlatList} from 'react-native';
import api from '../api/apiClient';

export default function Notifications() {
  const [notifications, setNotifications] = useState([]);

  useEffect(()=>{
    const fetchNotifications = async ()=>{
      const res = await api.get('/notifications.php');
      setNotifications(res.data.notifications);
    };
    fetchNotifications();
  },[]);

  return (
    <View style={{flex:1, padding:10}}>
      <FlatList
        data={notifications}
        keyExtractor={(item)=>item.id.toString()}
        renderItem={({item})=>(
          <Text style={{marginBottom:5}}>
            {item.message} ({item.created_at})
          </Text>
        )}
      />
    </View>
  );
}