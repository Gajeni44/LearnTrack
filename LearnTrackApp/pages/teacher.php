<!DOCTYPE html>
<html>
<head>
    <title>LearnTrack | Teacher Portal</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .absent-row { background-color: #ffe6e6; } /* Highlight for quick scanning */
        .status-present { color: green; font-weight: bold; }
        .status-absent { color: red; font-weight: bold; }
        .alert-box { background: #ffcc00; padding: 10px; margin-bottom: 10px; border-radius: 5px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>Teacher: Class 10-A Register</h1>
        <p>Date: <?php echo date("Y-m-d"); ?></p>
    </div>

    <div class="container">
        <div class="card" style="grid-column: span 2;">
            <h3>Daily Attendance Register</h3>
            <p><i>Note: Saving data daily builds the Term/Yearly reports for the Principal.</i></p>
            
            <table width="100%" border="1" style="border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background: #eee;">
                        <th>Student Name</th>
                        <th>Status</th>
                        <th>Consecutive Absences</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>John Sipho</td>
                        <td class="status-present">Present</td>
                        <td>0</td>
                        <td><button style="background:red;">Mark Absent</button></td>
                    </tr>
                    <tr class="absent-row">
                        <td>Sarah Dlamini</td>
                        <td class="status-absent">Absent</td>
                        <td>3</td>
                        <td>
                            <button onclick="alert('3-DAY ALERT: Sending Formal Invitation to Sarah\'s parents for a meeting.')" style="background:black;">SEND MEETING INVITATION</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <br>
            <button style="background:green;">SAVE & SUBMIT DAILY REGISTER</button>
        </div>

        <div class="card">
            <h3>Quick Notify Parent</h3>
            <p>Notify parents of single-day absence instantly.</p>
            <select style="width:100%; padding:10px;">
                <option>Select Student...</option>
                <option>John Sipho</option>
                <option>Sarah Dlamini</option>
            </select>
            <button style="margin-top:10px;" onclick="alert('SMS Sent: Your child is marked absent today. Please provide a reason.')">
                CLICK TO NOTIFY PARENT
            </button>
        </div>
    </div>
</body>
</html>"></textarea>