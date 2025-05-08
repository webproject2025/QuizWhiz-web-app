export class Timer {
    constructor(duration, displayElement, onTimeout) {
        this.duration = duration;
        this.displayElement = displayElement;
        this.onTimeout = onTimeout;
        this.remainingTime = duration;
        this.interval = null;
    }

    start() {
        if (this.interval) return;
        this.interval = setInterval(() => {
            this.remainingTime--;
            this.displayElement.textContent = this.remainingTime;
            if (this.remainingTime <= 0) {
                this.stop();
                this.onTimeout?.();
            }
        }, 1000);
    }

    stop() {
        clearInterval(this.interval);
        this.interval = null;
    }

    reset(newDuration) {
        this.stop();
        this.duration = newDuration || this.duration;
        this.remainingTime = this.duration;
        this.displayElement.textContent = this.remainingTime;
    }
}