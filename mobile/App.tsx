import { StatusBar } from 'expo-status-bar';
import * as ImagePicker from 'expo-image-picker';
import { useCallback, useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  Image,
  Linking,
  Platform,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';

const API_URL = 'http://127.0.0.1:8010/api';

type CustomerStatus = 'not_called' | 'called' | 'came';

type Customer = {
  id: number;
  first_name: string;
  last_name: string;
  personal_id: string;
  address: string | null;
  phone: string | null;
  status: CustomerStatus;
  image_url: string | null;
};

const STATUS_LABEL: Record<CustomerStatus, string> = {
  not_called: 'არ დარეკილი',
  called: 'დარეკილი',
  came: 'მოვიდა',
};

type SortField = 'last_name' | 'first_name' | 'status';
type SortDir = 'asc' | 'desc';

const SORT_LABEL: Record<SortField, string> = {
  last_name: 'გვარით',
  first_name: 'სახელით',
  status: 'სტატუსით',
};

type Session = { token: string; name: string; email: string };

export default function App() {
  const [session, setSession] = useState<Session | null>(null);

  return (
    <View style={styles.root}>
      <StatusBar style="light" />
      {session ? (
        <CustomerList session={session} onLogout={() => setSession(null)} />
      ) : (
        <LoginScreen onLoggedIn={setSession} />
      )}
    </View>
  );
}

function LoginScreen({ onLoggedIn }: { onLoggedIn: (s: Session) => void }) {
  const [email, setEmail] = useState('gegagagua@gmail.com');
  const [password, setPassword] = useState('password');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleLogin() {
    setLoading(true);
    setError(null);
    try {
      const res = await fetch(`${API_URL}/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ email, password, device: Platform.OS }),
      });
      if (!res.ok) {
        const body = await res.json().catch(() => null);
        throw new Error(body?.message || `HTTP ${res.status}`);
      }
      const data = await res.json();
      onLoggedIn({ token: data.token, name: data.user.name, email: data.user.email });
    } catch (e: any) {
      setError(e?.message ?? 'შესვლა ვერ მოხერხდა');
    } finally {
      setLoading(false);
    }
  }

  return (
    <View style={styles.loginWrap}>
      <Text style={styles.title}>ადმინის შესვლა</Text>
      <Text style={styles.label}>ელფოსტა</Text>
      <TextInput
        style={styles.input}
        value={email}
        onChangeText={setEmail}
        autoCapitalize="none"
        keyboardType="email-address"
        autoCorrect={false}
      />
      <Text style={styles.label}>პაროლი</Text>
      <TextInput
        style={styles.input}
        value={password}
        onChangeText={setPassword}
        secureTextEntry
      />
      {error && <Text style={styles.error}>{error}</Text>}
      <Pressable
        style={[styles.btnPrimary, loading && { opacity: 0.5 }]}
        disabled={loading}
        onPress={handleLogin}
      >
        <Text style={styles.btnPrimaryText}>{loading ? 'შესვლა...' : 'შესვლა'}</Text>
      </Pressable>
      <Text style={styles.hint}>API: {API_URL}</Text>
    </View>
  );
}

function CustomerList({ session, onLogout }: { session: Session; onLogout: () => void }) {
  const [customers, setCustomers] = useState<Customer[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [updatingId, setUpdatingId] = useState<number | null>(null);
  const [search, setSearch] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [sort, setSort] = useState<SortField>('last_name');
  const [direction, setDirection] = useState<SortDir>('asc');

  const authHeaders = {
    Accept: 'application/json',
    Authorization: `Bearer ${session.token}`,
  };

  useEffect(() => {
    const t = setTimeout(() => setDebouncedSearch(search.trim()), 300);
    return () => clearTimeout(t);
  }, [search]);

  const load = useCallback(async () => {
    setError(null);
    try {
      const params = new URLSearchParams({ sort, direction });
      if (debouncedSearch) params.set('search', debouncedSearch);
      const res = await fetch(`${API_URL}/customers?${params.toString()}`, {
        headers: authHeaders,
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();
      setCustomers(data.data);
    } catch (e: any) {
      setError(e?.message ?? 'ჩატვირთვა ვერ მოხერხდა');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [session.token, debouncedSearch, sort, direction]);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    const id = setInterval(load, 30000);
    return () => clearInterval(id);
  }, [load]);

  function toggleSort(field: SortField) {
    if (field === sort) {
      setDirection((d) => (d === 'asc' ? 'desc' : 'asc'));
    } else {
      setSort(field);
      setDirection('asc');
    }
  }

  async function setStatus(customer: Customer, status: CustomerStatus) {
    setUpdatingId(customer.id);
    try {
      const res = await fetch(`${API_URL}/customers/${customer.id}/status`, {
        method: 'POST',
        headers: { ...authHeaders, 'Content-Type': 'application/json' },
        body: JSON.stringify({ status }),
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();
      setCustomers((prev) => prev.map((c) => (c.id === customer.id ? data.data : c)));
    } catch (e: any) {
      Alert.alert('შეცდომა', e?.message ?? 'სტატუსი ვერ განახლდა');
    } finally {
      setUpdatingId(null);
    }
  }

  async function pickAndUpload(customer: Customer, source: 'camera' | 'library') {
    const perm =
      source === 'camera'
        ? await ImagePicker.requestCameraPermissionsAsync()
        : await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!perm.granted) {
      Alert.alert('შეცდომა', 'წვდომა უარყოფილია');
      return;
    }

    const pickerResult =
      source === 'camera'
        ? await ImagePicker.launchCameraAsync({ quality: 0.7, allowsEditing: true })
        : await ImagePicker.launchImageLibraryAsync({
            quality: 0.7,
            allowsEditing: true,
            mediaTypes: ['images'],
          });

    if (pickerResult.canceled || !pickerResult.assets?.length) return;
    const asset = pickerResult.assets[0];

    setUpdatingId(customer.id);
    try {
      const form = new FormData();
      const uri = asset.uri;
      const name = uri.split('/').pop() || `photo-${Date.now()}.jpg`;
      const ext = (name.split('.').pop() || 'jpg').toLowerCase();
      const type = asset.mimeType || (ext === 'png' ? 'image/png' : 'image/jpeg');
      form.append('image', { uri, name, type } as any);

      const res = await fetch(`${API_URL}/customers/${customer.id}/image`, {
        method: 'POST',
        headers: { ...authHeaders },
        body: form,
      });
      if (!res.ok) {
        const body = await res.json().catch(() => null);
        throw new Error(body?.message || `HTTP ${res.status}`);
      }
      const data = await res.json();
      setCustomers((prev) => prev.map((c) => (c.id === customer.id ? data.data : c)));
    } catch (e: any) {
      Alert.alert('შეცდომა', e?.message ?? 'ატვირთვა ვერ მოხერხდა');
    } finally {
      setUpdatingId(null);
    }
  }

  async function removeImage(customer: Customer) {
    setUpdatingId(customer.id);
    try {
      const res = await fetch(`${API_URL}/customers/${customer.id}/image`, {
        method: 'DELETE',
        headers: authHeaders,
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();
      setCustomers((prev) => prev.map((c) => (c.id === customer.id ? data.data : c)));
    } catch (e: any) {
      Alert.alert('შეცდომა', e?.message ?? 'წაშლა ვერ მოხერხდა');
    } finally {
      setUpdatingId(null);
    }
  }

  function openPhotoMenu(customer: Customer) {
    const actions: { text: string; onPress?: () => void; style?: 'destructive' | 'cancel' }[] = [
      { text: 'გადაღება', onPress: () => pickAndUpload(customer, 'camera') },
      { text: 'გალერეიდან', onPress: () => pickAndUpload(customer, 'library') },
    ];
    if (customer.image_url) {
      actions.push({ text: 'წაშლა', style: 'destructive', onPress: () => removeImage(customer) });
    }
    actions.push({ text: 'გაუქმება', style: 'cancel' });
    Alert.alert('სურათი', undefined, actions);
  }

  async function callCustomer(customer: Customer) {
    if (!customer.phone) {
      Alert.alert('შეცდომა', 'ტელეფონი მითითებული არ არის');
      return;
    }
    const url = `tel:${customer.phone}`;
    const ok = await Linking.canOpenURL(url);
    if (!ok) {
      Alert.alert('შეცდომა', 'დარეკვა შეუძლებელია');
      return;
    }
    await Linking.openURL(url);
    if (customer.status === 'not_called') {
      setStatus(customer, 'called');
    }
  }

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" />
      </View>
    );
  }

  return (
    <View style={{ flex: 1 }}>
      <View style={styles.header}>
        <View>
          <Text style={styles.headerName}>{session.name}</Text>
          <Text style={styles.headerEmail}>{session.email}</Text>
        </View>
        <Pressable onPress={onLogout} style={styles.logoutBtn}>
          <Text style={styles.logoutText}>გასვლა</Text>
        </Pressable>
      </View>

      {error && <Text style={styles.errorBanner}>{error}</Text>}

      <View style={styles.toolbar}>
        <TextInput
          style={styles.searchInput}
          value={search}
          onChangeText={setSearch}
          placeholder="ძებნა (სახელი, გვარი, ტელეფონი...)"
          placeholderTextColor="#999"
          autoCapitalize="none"
          autoCorrect={false}
        />
        <View style={styles.sortRow}>
          {(Object.keys(SORT_LABEL) as SortField[]).map((field) => {
            const active = sort === field;
            return (
              <Pressable
                key={field}
                onPress={() => toggleSort(field)}
                style={[styles.chip, active && styles.chipActive]}
              >
                <Text style={active ? styles.chipTextActive : styles.chipText}>
                  {SORT_LABEL[field]}
                  {active ? (direction === 'asc' ? ' ▲' : ' ▼') : ''}
                </Text>
              </Pressable>
            );
          })}
        </View>
      </View>

      <FlatList
        data={customers}
        keyExtractor={(c) => String(c.id)}
        contentContainerStyle={{ padding: 16 }}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => {
              setRefreshing(true);
              load();
            }}
          />
        }
        ListEmptyComponent={<Text style={styles.empty}>ჩანაწერები არ არის.</Text>}
        renderItem={({ item }) => (
          <CustomerCard
            customer={item}
            updating={updatingId === item.id}
            onCall={() => callCustomer(item)}
            onMarkCame={() => setStatus(item, 'came')}
            onPhoto={() => openPhotoMenu(item)}
          />
        )}
      />
    </View>
  );
}

function CustomerCard({
  customer,
  updating,
  onCall,
  onMarkCame,
  onPhoto,
}: {
  customer: Customer;
  updating: boolean;
  onCall: () => void;
  onMarkCame: () => void;
  onPhoto: () => void;
}) {
  const badgeStyle =
    customer.status === 'came'
      ? [styles.badge, styles.badgeCame]
      : customer.status === 'called'
        ? [styles.badge, styles.badgeCalled]
        : [styles.badge, styles.badgeNot];
  const badgeTextStyle =
    customer.status === 'came'
      ? styles.badgeCameText
      : customer.status === 'called'
        ? styles.badgeCalledText
        : styles.badgeNotText;

  const isCame = customer.status === 'came';

  return (
    <View style={[styles.card, isCame && styles.cardCame]}>
      <View style={styles.cardHeader}>
        <Pressable
          onPress={onPhoto}
          disabled={updating}
          style={[styles.avatar, updating && { opacity: 0.5 }]}
        >
          {customer.image_url ? (
            <Image source={{ uri: customer.image_url }} style={styles.avatarImg} />
          ) : (
            <Text style={styles.avatarPlaceholder}>＋</Text>
          )}
        </Pressable>
        <Text style={styles.name}>
          {customer.first_name} {customer.last_name}
        </Text>
      </View>

      <Row label="პირადობა" value={customer.personal_id} />
      <Row label="მისამართი" value={customer.address} />
      <Row label="ტელეფონი" value={customer.phone} />

      <View style={styles.statusRow}>
        <Text style={styles.label}>სტატუსი:</Text>
        <View style={badgeStyle}>
          <Text style={badgeTextStyle}>{STATUS_LABEL[customer.status]}</Text>
        </View>
      </View>

      {customer.status !== 'came' && (
        <View style={styles.actions}>
          {customer.status === 'not_called' && (
            <Pressable style={styles.callBtn} onPress={onCall}>
              <Text style={styles.callBtnText}>📞 დარეკვა</Text>
            </Pressable>
          )}
          <Pressable
            style={[styles.markBtn, updating && { opacity: 0.5 }]}
            disabled={updating}
            onPress={onMarkCame}
          >
            <Text style={styles.markBtnText}>{updating ? '...' : '✓ მოვიდა'}</Text>
          </Pressable>
        </View>
      )}
    </View>
  );
}

function Row({ label, value }: { label: string; value: string | null | undefined }) {
  return (
    <View style={styles.row}>
      <Text style={styles.rowLabel}>{label}</Text>
      <Text style={styles.rowValue}>{value || '—'}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: '#f5f5f7' },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },

  loginWrap: {
    flex: 1,
    justifyContent: 'center',
    padding: 24,
    backgroundColor: '#f5f5f7',
  },
  title: { fontSize: 24, fontWeight: '700', marginBottom: 24, textAlign: 'center' },
  label: { fontSize: 13, color: '#555', marginBottom: 6, marginTop: 8 },
  input: {
    borderWidth: 1,
    borderColor: '#d0d0d5',
    borderRadius: 8,
    padding: 12,
    backgroundColor: '#fff',
    fontSize: 16,
  },
  btnPrimary: {
    marginTop: 20,
    backgroundColor: '#0071e3',
    padding: 14,
    borderRadius: 8,
    alignItems: 'center',
  },
  btnPrimaryText: { color: '#fff', fontSize: 16, fontWeight: '600' },
  error: { color: '#a12626', marginTop: 10 },
  hint: { color: '#888', fontSize: 12, marginTop: 16, textAlign: 'center' },

  header: {
    backgroundColor: '#1d1d1f',
    paddingTop: 56,
    paddingBottom: 14,
    paddingHorizontal: 16,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-end',
  },
  headerName: { color: '#fff', fontSize: 16, fontWeight: '600' },
  headerEmail: { color: '#aaa', fontSize: 12 },
  logoutBtn: { borderColor: '#444', borderWidth: 1, paddingHorizontal: 12, paddingVertical: 6, borderRadius: 6 },
  logoutText: { color: '#fff' },

  errorBanner: { backgroundColor: '#fdecec', color: '#a12626', padding: 10, textAlign: 'center' },
  empty: { textAlign: 'center', color: '#666', marginTop: 40 },

  toolbar: {
    paddingHorizontal: 16,
    paddingTop: 12,
    paddingBottom: 4,
    backgroundColor: '#f5f5f7',
    gap: 10,
  },
  searchInput: {
    borderWidth: 1,
    borderColor: '#d0d0d5',
    borderRadius: 10,
    paddingHorizontal: 12,
    paddingVertical: 10,
    backgroundColor: '#fff',
    fontSize: 15,
  },
  sortRow: { flexDirection: 'row', gap: 8, flexWrap: 'wrap' },
  chip: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 999,
    borderWidth: 1,
    borderColor: '#d0d0d5',
    backgroundColor: '#fff',
  },
  chipActive: { backgroundColor: '#0071e3', borderColor: '#0071e3' },
  chipText: { color: '#1d1d1f', fontSize: 13 },
  chipTextActive: { color: '#fff', fontSize: 13, fontWeight: '600' },

  card: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    marginBottom: 12,
    shadowColor: '#000',
    shadowOpacity: 0.05,
    shadowRadius: 4,
    shadowOffset: { width: 0, height: 1 },
    elevation: 1,
  },
  cardCame: {
    backgroundColor: '#d4ecdd',
    borderWidth: 1,
    borderColor: '#7cb693',
  },
  cardHeader: { flexDirection: 'row', alignItems: 'center', gap: 12, marginBottom: 10 },
  avatar: {
    width: 56,
    height: 56,
    borderRadius: 28,
    backgroundColor: '#eef0f4',
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: '#d0d0d5',
  },
  avatarImg: { width: '100%', height: '100%' },
  avatarPlaceholder: { fontSize: 22, color: '#888' },
  name: { flex: 1, fontSize: 18, fontWeight: '700' },
  row: { flexDirection: 'row', marginBottom: 4 },
  rowLabel: { width: 90, color: '#666', fontSize: 13 },
  rowValue: { flex: 1, fontSize: 14, color: '#1d1d1f' },

  statusRow: { flexDirection: 'row', alignItems: 'center', marginTop: 10, gap: 8 },
  badge: { paddingHorizontal: 10, paddingVertical: 4, borderRadius: 999 },
  badgeCame: { backgroundColor: '#e6f8ee' },
  badgeCameText: { color: '#1b7a3e', fontWeight: '600', fontSize: 12 },
  badgeCalled: { backgroundColor: '#fff4d6' },
  badgeCalledText: { color: '#8a5a00', fontWeight: '600', fontSize: 12 },
  badgeNot: { backgroundColor: '#fdecec' },
  badgeNotText: { color: '#a12626', fontWeight: '600', fontSize: 12 },

  actions: { flexDirection: 'row', gap: 10, marginTop: 14 },
  callBtn: {
    flex: 1,
    backgroundColor: '#e8f0ff',
    padding: 12,
    borderRadius: 8,
    alignItems: 'center',
  },
  callBtnText: { color: '#0055c4', fontWeight: '600' },
  markBtn: {
    flex: 1,
    backgroundColor: '#1b7a3e',
    padding: 12,
    borderRadius: 8,
    alignItems: 'center',
  },
  markBtnText: { color: '#fff', fontWeight: '600' },
});
