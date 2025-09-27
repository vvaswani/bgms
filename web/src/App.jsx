import { useState, useEffect, useMemo } from 'react'
import axios from 'axios'
import { Line } from 'react-chartjs-2'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js'
import './App.css'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend)

function App() {
  const [readings, setReadings] = useState([])
  const [newReading, setNewReading] = useState({
    value: '',
    note: '',
    isFasting: false,
  })
  const [loading, setLoading] = useState(false)
  const [loadingUser, setLoadingUser] = useState(true)
  const [error, setError] = useState(null)
  const [user, setUser] = useState(null)
  const [reports, setReports] = useState([])
  const [loadingReports, setLoadingReports] = useState(true)

  const apiUrl = import.meta.env.VITE_API_URL

  const getToken = () => localStorage.getItem('jwt_token')

  useEffect(() => {
    const url = new URL(window.location.href)
    const token = url.searchParams.get('token')
    if (token) {
      localStorage.setItem('jwt_token', token)
      url.searchParams.delete('token')
      window.history.replaceState({}, document.title, url.pathname + url.search)
    }
    fetchUserProfile()
  }, [])

  const fetchUserProfile = async () => {
    const token = getToken()
    if (!token) {
      setUser(null)
      setReadings([])
      setLoadingUser(false)
      return
    }
    try {
      const response = await axios.get(`${apiUrl}/api/users/me`, {
        headers: { Authorization: `Bearer ${token}` },
      })
      setUser(response.data)
      fetchReadings()
      fetchReports()

      // get the timezone for use in reports and table
      const inferredTz = useMemo(() => Intl.DateTimeFormat().resolvedOptions().timeZone, []);
      if (inferredTz && response.data.timezone !== inferredTz) {
        try {
          await axios.put(
            `${apiUrl}/api/users/me`,
            { timezone: inferredTz },
            { headers: { Authorization: `Bearer ${token}` } }
          )
        } catch (err) {
          console.error('Failed to update timezone:', err)
        }
      }

    } catch {
      setUser(null)
      setReadings([])
    } finally {
      setLoadingUser(false)
    }
  }

  const fetchReadings = async () => {
    const token = getToken()
    if (!token) return
    try {
      setLoading(true)
      const response = await axios.get(`${apiUrl}/api/readings`, {
        headers: { Authorization: `Bearer ${token}` },
      })
      setReadings(response.data)
      setError(null)
    } catch {
      setError('Failed to fetch readings')
    } finally {
      setLoading(false)
    }
  }

  const fetchReports = async () => {
    const token = getToken()
    if (!token) return

    try {
      setLoadingReports(true)
      const response = await axios.get(`${apiUrl}/api/reports`, {
        headers: { Authorization: `Bearer ${token}` },
      })

      // Sort by date and limit to last 7
      const sorted = response.data
        .sort((a, b) => new Date(b.date) - new Date(a.date))
        .slice(0, 7)

      setReports(sorted)
    } catch (err) {
      console.error('Failed to fetch reports:', err)
    } finally {
      setLoadingReports(false)
    }
  }

  const handleGoogleLogin = () => {
    window.location.href = `${apiUrl}/connect/google`
  }

  const handleLogout = () => {
    setUser(null)
    setReadings([])
    localStorage.removeItem('jwt_token')
    window.location.reload()
  }

  const handleAddReading = async (e) => {
    e.preventDefault()

    if (newReading.value === '' || isNaN(newReading.value)) {
      setError('Please enter a valid glucose value')
      return
    }

    const token = getToken()
    if (!token) {
      setError('Not authenticated')
      return
    }

    try {
      setLoading(true)
      await axios.post(
        `${apiUrl}/api/readings`,
        {
          value: parseFloat(newReading.value),
          note: newReading.note || null,
          isFasting: newReading.isFasting,
        },
        { headers: { Authorization: `Bearer ${token}` } }
      )
      setNewReading({ value: '', note: '', isFasting: false })
      fetchReadings()
      setError(null)
    } catch {
      setError('Failed to add reading')
    } finally {
      setLoading(false)
    }
  }

  const chartData = useMemo(() => {
    const sortedReadings = [...readings].sort(
      (a, b) => new Date(a.createdAt) - new Date(b.createdAt)
    )
    return {
      labels: sortedReadings.map((r) =>
        new Date(r.createdAt).toLocaleDateString(undefined, {
          month: 'short',
          day: 'numeric',
          year: 'numeric',
        })
      ),
      datasets: [
        {
          label: 'Glucose Value (mg/dL)',
          data: sortedReadings.map((r) => r.value),
          fill: false,
          borderColor: 'rgba(75, 192, 192, 1)',
          backgroundColor: 'rgba(75, 192, 192, 0.2)',
          tension: 0.3,
          pointRadius: 4,
          pointHoverRadius: 6,
        },
      ],
    }
  }, [readings])

  const chartOptions = {
    responsive: true,
    plugins: {
      legend: {
        position: 'top',
      },
      title: {
        display: false,
        text: 'Glucose Readings Over Time',
      },
    },
    scales: {
      y: {
        beginAtZero: false,
        title: {
          display: true,
          text: 'Glucose Value (mg/dL)',
        },
      },
      x: {
        title: {
          display: true,
          text: 'Date',
        },
      },
    },
  }

  return (
    <>
      <div className="bg-primary text-white py-5 text-center">
        <div className="container">
          <h1 className="display-4 fw-bold">Welcome to S.U.G.A.R</h1>
          <p className="lead">Smart Universal Glucose Analysis and Reporting</p>

          {!loadingUser && !user ? (
            <button onClick={handleGoogleLogin} className="btn btn-light mt-3" type="button">
              Sign in with Google
            </button>
          ) : user ? (
            <div className="mt-3">
              <span>Welcome, {user.name ? user.name : user.email}</span>
              <button onClick={handleLogout} className="btn btn-light ms-3" type="button">
                Logout
              </button>
            </div>
          ) : (
            <p>Checking login status...</p>
          )}
        </div>
      </div>

      {!loadingUser && user && (
        <>
          {/* form */}
          <div className="container my-5 p-4 border rounded shadow-sm bg-light">
            <h2 className="mb-4">Add Reading</h2>
            <form onSubmit={handleAddReading}>
              <div className="mb-3">
                <label htmlFor="glucoseValue" className="form-label">
                  Glucose Value (mg/dL)
                </label>
                <input
                  id="glucoseValue"
                  type="number"
                  step="0.01"
                  className="form-control"
                  placeholder="e.g. 120"
                  value={newReading.value}
                  onChange={(e) => setNewReading({ ...newReading, value: e.target.value })}
                  required
                />
              </div>

              <div className="mb-3">
                <label htmlFor="glucoseNote" className="form-label">
                  Note (optional)
                </label>
                <input
                  id="glucoseNote"
                  type="text"
                  className="form-control"
                  value={newReading.note}
                  onChange={(e) => setNewReading({ ...newReading, note: e.target.value })}
                />
              </div>

              <div className="mb-3">
                <label htmlFor="fastingSelect" className="form-label">
                  Fasting
                </label>
                <select
                  id="fastingSelect"
                  className="form-select"
                  value={newReading.isFasting ? 'yes' : 'no'}
                  onChange={(e) =>
                    setNewReading({ ...newReading, isFasting: e.target.value === 'yes' })
                  }
                >
                  <option value="no">No</option>
                  <option value="yes">Yes</option>
                </select>
              </div>

              <button type="submit" disabled={loading} className="btn btn-primary">
                Add Reading
              </button>

              {error && <p className="text-danger mt-3">{error}</p>}
            </form>
          </div>

          {/* chart */}
          <div className="container my-5 p-4 border rounded shadow-sm bg-white">
            <h2 className="mb-4">Trend</h2>
            {readings.length === 0 ? (
              <p className="text-muted text-center">No data available to display chart.</p>
            ) : (
              <Line data={chartData} options={chartOptions} />
            )}
          </div>

          {/* table */}
          <div className="container my-5 p-4 border rounded shadow-sm bg-light">
            <h2 className="mb-4">Previous Readings</h2>

            {loading ? (
              <p>Loading readings...</p>
            ) : readings.length === 0 ? (
              <p>No readings yet.</p>
            ) : (
              <div className="table-responsive">
                <table className="table table-bordered table-striped align-middle">
                  <thead className="table-light">
                    <tr>
                      <th scope="col">Value (mg/dL)</th>
                      <th scope="col">Note</th>
                      <th scope="col">Fasting</th>
                      <th scope="col">Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    {readings.map((r) => (
                      <tr key={r.id}>
                        <td>{r.value}</td>
                        <td>{r.note || '—'}</td>
                        <td>{r.isFasting ? 'Yes' : 'No'}</td>
                        <td>{new Date(r.createdAt).toLocaleString(undefined, {timeZone: user?.timezone || inferredTz, timeZoneName: 'shortGeneric'})}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>

          {/* reports section */}
          <div className="container my-5 p-4 border rounded shadow-sm bg-white">
            <h2 className="mb-4">Last 7 Reports</h2>

            {loadingReports ? (
              <p>Loading reports...</p>
            ) : reports.length === 0 ? (
              <p>No reports available.</p>
            ) : (
              <div className="table-responsive">
                <table className="table table-bordered table-striped align-middle">
                  <thead className="table-light">
                    <tr>
                      <th scope="col">Date</th>
                      <th scope="col">Download</th>
                    </tr>
                  </thead>
                  <tbody>
                    {reports.map((report) => (
                      <tr key={report.id}>
                        <td>{new Date(report.date).toLocaleDateString(undefined, {timeZone: user?.timezone || inferredTz,timeZoneName: 'shortGeneric'})}
                        </td>
                        <td>
                          <a
                            href={`${apiUrl}/download-report/${report.filename}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="btn btn-sm btn-primary"
                          >
                            Download PDF
                          </a>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>



        </>
      )}

      {!loadingUser && !user && (
        <div className="container my-5">
          <p>Please sign in to view and add glucose readings.</p>
        </div>
      )}
    </>
  )
}

export default App
