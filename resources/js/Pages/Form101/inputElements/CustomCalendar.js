import React, { useEffect, useState, useMemo } from "react";
import DatePicker, { registerLocale } from "react-datepicker";
import moment from "moment";
import Swal from "sweetalert2";
import { useAlert } from "react-alert";
import { useTranslation } from "react-i18next";
import i18next from "i18next";

import "react-datepicker/dist/react-datepicker.css";
import "./customCalendar.css";
import { createHalfHourlyTimeArray } from "../../../Utils/job.utils";

import { enUS, he, tr } from "date-fns/locale";
import FullPageLoader from "../../../Components/common/FullPageLoader";
registerLocale("en", enUS);
registerLocale("he", he);

const CustomCalendar = ({ meeting, start_time, meetingDate }) => {
  const [selectedDate, setSelectedDate] = useState(new Date());
  const [selectedTime, setSelectedTime] = useState(null);
  const [availableSlots, setAvailableSlots] = useState([]);
  const [bookedSlots, setBookedSlots] = useState([]);
  const [isLoading, setIsLoading] = useState(false);
  const { t } = useTranslation();
  const alert = useAlert();

  const currentLanguage = meeting?.client?.lng === "heb" ? "he" : "en";
  moment.locale(currentLanguage);

  const getTeamAvailibality = (date) => {
    const _date = moment(date).format("Y-MM-DD");

    axios
      .get(`/api/teams/availability/${meeting.team_id}/date/${_date}`)
      .then((response) => {
        setAvailableSlots(
          response.data.available_slots.map((i) => {
            return {
              start_time: i.start_time.slice(0, -3),
              end_time: i.end_time.slice(0, -3),
            };
          })
        );
        setBookedSlots(response.data.booked_slots);
      })
      .catch((e) => {
        setAvailableSlots([]);
        setBookedSlots([]);

        Swal.fire({
          title: "Error!",
          text: e.response.data.message,
          icon: "error",
        });
      });
  };


  const timeOptions = useMemo(() => {
    return createHalfHourlyTimeArray("08:00", "24:00");
  }, []);

  const startTimeOptions = useMemo(() => {
    const _timeOptions = timeOptions.filter((_option) => {
      console.log(_option);
      
      if (_option === "24:00") {
        return false;
      }
  
      // Handle meeting time explicitly
      if (meeting && meeting.start_time && meetingDate) {
        const meetingStartTime = moment(meeting.start_time, "hh:mm A").format("kk:mm");
        const isMeetingDateSame = moment(meetingDate, "DD-MM-YYYY").isSame(moment(selectedDate), "day");
        console.log(meetingStartTime, _option, isMeetingDateSame);
        
        if (meetingStartTime === _option && isMeetingDateSame) {
          return true;
        }
      }
  
      // Parse the current time option
      const _startTime = moment(_option, "kk:mm");
  
      // Check available slots
      const isSlotAvailable = availableSlots.some((slot) => {
        const _slotStartTime = moment(slot.start_time, "kk:mm");
        const _slotEndTime = moment(slot.end_time, "kk:mm");
  
        return (
          _slotStartTime.isSame(_startTime) ||
          _startTime.isBetween(_slotStartTime, _slotEndTime)
        );
      });
  
      if (!isSlotAvailable) {
        return false;
      }
  
      // Check booked slots
      return !bookedSlots.some((slot) => {
        const _slotStartTime = moment(slot.start_time, "kk:mm");
        const _slotEndTime = moment(slot.end_time, "kk:mm");
  
        return (
          _startTime.isBetween(_slotStartTime, _slotEndTime) ||
          _startTime.isSame(_slotStartTime)
        );
      });
    });
  
    return _timeOptions;
  }, [timeOptions, availableSlots, bookedSlots, meeting, meetingDate, selectedDate]);
  

  // console.log(availableSlots, "availableSlots");
  

  useEffect(() => {
    getTeamAvailibality(selectedDate);
  }, [selectedDate]);

  const handleSubmit = () => {
    if (!selectedDate) {
      alert.error(t("meet_stat.date_not_selected"));
      return false;
    }

    if (!selectedTime) {
      alert.error(t("meet_stat.time_not_selected"));
      return false;
    }
    setIsLoading(true);

    axios
      .post(`/api/client/meeting/${meeting.id}/reschedule`, {
        start_date: selectedDate ? moment(selectedDate).format("YYYY-MM-DD") : null,
        start_time: selectedTime,
      })
      .then((response) => {
        setIsLoading(false);
        alert.success(response.data.message);
        setTimeout(() => {
          window.location.reload(true);
        }, 1000);
      })
      .catch((e) => {
        setIsLoading(false);

        Swal.fire({
          title: "Error!",
          text: e.response.data.message,
          icon: "error",
        });
      });
  };


  const today = moment().format("DD-MM-YYYY");


  const formattedSelectedDate = useMemo(() => {
    if (selectedDate) {
      const _date = moment(selectedDate);
      const monthName = _date.format("MMMM");
      const dayName = _date.format("dddd");
      const date = _date.date();

      return `${dayName}, ${monthName} ${date}`;
    }
    return "";
  }, [selectedDate, currentLanguage]);

  const selectDate = moment(selectedDate).format("DD-MM-YYYY");
  const res = moment(start_time, "hh:mm A").format("HH:mm");

  const timeSlots = useMemo(() => {
    const filteredTimeOptions = startTimeOptions.filter((timeOption) => {
      const formattedTime = moment(timeOption, "kk:mm").format("HH:mm");
      const isSelectedDateMeetingDate = moment(selectedDate).isSame(moment(meetingDate, "DD-MM-YYYY"), "day");
  
      // Exclude meeting's start_time only if the selected date matches the meeting date
      if (isSelectedDateMeetingDate && formattedTime === res) {
        return false; // Exclude
      }
      return true; // Include other times
    });
  
    return filteredTimeOptions.map((timeOption) =>
      moment(timeOption, "kk:mm").format("hh:mm A")
    );
  }, [startTimeOptions, selectedDate, meetingDate, res]);
  

  console.log(timeSlots, "timeSlots");
  

  const filteredTimeSlots = timeSlots.filter((t) => {
    const slotTime = moment(t, "hh:mm A");
  
    // Check if the selected date is today
    if (moment(selectedDate).isSame(moment(), "day")) {
      // Show only future time slots
      return slotTime.isAfter(moment());
    }
    return true; // Otherwise, show all slots
  });
  
  

  return (
    <>
      <div className="mx-auto custom-calendar">
        <div className="border">
          <h5 className="mt-3 pl-2">{t("client.meeting.reSchedule.selectDateAndTime")}</h5>
          <div className="d-flex gap-3 pt-3 flex-wrap" style={{ overflowX: "auto" }}>
            <div>
              <DatePicker
                selected={selectedDate}
                onChange={(date) => setSelectedDate(date)}
                autoFocus
                shouldCloseOnSelect={false}
                inline
                minDate={new Date()}
                locale={currentLanguage}
              />
            </div>
            <div className="mt-1">
              <h6 className="time-slot-date">{formattedSelectedDate}</h6>
              <ul className="list-unstyled mt-4 timeslot">
                {filteredTimeSlots.length > 0 ? (
                  filteredTimeSlots.map((t, index) => {
                    return (
                      <li
                        className={`py-2 px-3 border mb-2 text-center border-primary ${selectedTime === t ? "bg-primary text-white" : "text-primary"
                          }`}
                        style={{
                          backgroundColor: selectDate === meetingDate && start_time === t ? "#dbdbdb" : undefined,
                        }}
                        disabled={selectDate === meetingDate && start_time === t}
                        key={index}
                        onClick={() => {
                          if (selectDate === meetingDate && start_time === t) {
                            alert.error("You can't select the same time");
                            return;
                          }
                          setSelectedTime(t);
                        }}
                      >
                        {t}
                      </li>
                    )
                  })
                ) : (
                  <li className="py-2 px-3 border mb-2 text-center border-secondary text-secondary bg-light">
                    {t("global.noTimeSlot")} {t("global.available")}
                  </li>
                )}
              </ul>
            </div>
          </div>
        </div>
      </div>
      <button
        type="button"
        className="btn btn-primary mt-2"
        onClick={handleSubmit}
        disabled={isLoading}
      >
        {t("common.submit")}
      </button>
      {isLoading && <FullPageLoader visible={isLoading} />}
    </>
  );
};

export default CustomCalendar;
